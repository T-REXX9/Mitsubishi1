<?php
// Start session first
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

// If user is already logged in, redirect them to their dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'Customer') {
        header("Location: customer.php");
        exit;
    } elseif ($_SESSION['user_role'] === 'Admin' || $_SESSION['user_role'] === 'SalesAgent') {
        header("Location: main/dashboard.php");
        exit;
    }
}

include_once(dirname(__DIR__) . '/includes/database/db_conn.php');
require_once(dirname(__DIR__) . '/includes/services/LoginSecurityService.php');

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}

$login_error = '';
$loginSecurity = new \Mitsubishi\Services\LoginSecurityService($connect);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Validate CSRF token
  $token = $_POST['csrf_token'] ?? '';
  if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    $login_error = "Security validation failed. Please try again.";
  } else {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic input validation
    if (empty($email) || empty($password)) {
      $login_error = "Please enter both email and password.";
    } else {
      // Check IP-based rate limiting
      $ipLockout = $loginSecurity->isIPLockedOut($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
      if ($ipLockout['locked']) {
        $minutes = ceil($ipLockout['remaining_time'] / 60);
        $login_error = "Too many failed login attempts from your IP address. Please try again in $minutes minute(s).";
      } else {
        // Check email-based rate limiting
        $emailLockout = $loginSecurity->isEmailLockedOut($email);
        if ($emailLockout['locked']) {
          $minutes = ceil($emailLockout['remaining_time'] / 60);
          $login_error = "Too many failed login attempts. Please try again in $minutes minute(s).";
        } else {
          // Fetch account by email
          $stmt = $connect->prepare("SELECT *, COALESCE(IsDisabled, 0) AS IsDisabled FROM accounts WHERE Email = ?");
          $stmt->execute([$email]);
          $account = $stmt->fetch(PDO::FETCH_ASSOC);

          // Check if account is disabled
          if ($account && intval($account['IsDisabled'] ?? 0) === 1) {
            $loginSecurity->recordAttempt($email, false);
            $login_error = "Your account has been disabled. Please contact support.";
          }
          // Verify password
          elseif ($account && password_verify($password, $account['PasswordHash'])) {
            // Successful login - record it
            $loginSecurity->recordAttempt($email, true);
            $loginSecurity->clearAttempts($email);

            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            // Check email verification for customers
            if ($account['Role'] === 'Customer' && isset($account['email_verified']) && $account['email_verified'] == 0) {
              // Email not verified, redirect to OTP verification
              $_SESSION['pending_verification_user_id'] = $account['Id'];
              $_SESSION['pending_verification_email'] = $account['Email'];

              // Resend OTP
              require_once(dirname(__DIR__) . '/includes/services/OTPService.php');
              $otpService = new \Mitsubishi\Services\OTPService($connect);
              $otpService->sendOTP($account['Id'], $account['Email']);

              header("Location: verify_otp.php");
              exit;
            }

            // Update LastLoginAt
            $update = $connect->prepare("UPDATE accounts SET LastLoginAt = NOW() WHERE Id = ?");
            $update->execute([$account['Id']]);

            // If user is a Sales Agent, update their status to Active
            if ($account['Role'] === 'SalesAgent') {
              // Check if agent profile exists
              $checkAgent = $connect->prepare("SELECT agent_profile_id FROM sales_agent_profiles WHERE account_id = ?");
              $checkAgent->execute([$account['Id']]);
              $agentProfile = $checkAgent->fetch();

              if ($agentProfile) {
                // Update status to Active
                $updateStatus = $connect->prepare("UPDATE sales_agent_profiles SET status = 'Active' WHERE account_id = ?");
                $updateStatus->execute([$account['Id']]);
              } else {
                // Create agent profile with Active status
                $createAgent = $connect->prepare("INSERT INTO sales_agent_profiles (account_id, agent_id_number, status) VALUES (?, ?, 'Active')");
                $createAgent->execute([$account['Id'], 'SA-' . str_pad($account['Id'], 3, '0', STR_PAD_LEFT)]);
              }
            }

            // Set session variables
            $_SESSION['user_id'] = $account['Id'];
            $_SESSION['user_role'] = $account['Role'];
            $_SESSION['user_email'] = $account['Email'];
            $_SESSION['username'] = $account['Username'];

            // Redirect based on role
            if ($account['Role'] === 'Customer') {
              // Check if customer information exists
              $stmt_check_info = $connect->prepare("SELECT cusID FROM customer_information WHERE account_id = ?");
              $stmt_check_info->execute([$account['Id']]);
              $customer_info = $stmt_check_info->fetch(PDO::FETCH_ASSOC);

              if ($customer_info) {
                // Customer has filled out information, redirect to dashboard
                header("Location: customer.php");
              } else {
                // Customer needs to fill out verification form
                header("Location: verification.php");
              }
            } elseif ($account['Role'] === 'Admin') {
              header("Location: main/dashboard.php");
            } elseif ($account['Role'] === 'SalesAgent') {
              header("Location: main/dashboard.php");
            } else {
              // Fallback for any unexpected role values
              header("Location: unauthorized.php");
            }
            exit;
          }
          // Invalid credentials
          else {
            // Record failed attempt (use email only if account exists to prevent enumeration tracking)
            $loginSecurity->recordAttempt($account ? $email : null, false);

            // Generic error message to prevent user enumeration
            $remainingAttempts = $loginSecurity->getRemainingAttempts($email);
            if ($remainingAttempts > 0 && $remainingAttempts <= 3) {
              $login_error = "Invalid email or password. You have $remainingAttempts attempt(s) remaining.";
            } else {
              $login_error = "Invalid email or password.";
            }
          }
        }
      }
    }
  }

  // Regenerate CSRF token after each attempt
  try {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  } catch (Exception $e) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mitsubishi Motors Login</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', sans-serif;
    }

    html,
    body {
      height: 100%;
      width: 100%;
      margin: 0;
      padding: 0;
      overflow: hidden;
    }

    body {
      /*wag po i-uncomment yung background image kasi pinapatanggal samin yun*/
      /*background-image: url(../includes/images/logbg.jpg);*/
      background-color: #222222;
      background-size: cover; /* scales image to cover the whole area */
      background-position: center; /* centers the image */
      background-repeat: no-repeat;
    }

    .container {
      display: flex;
      justify-content: center;
      align-items: center;
      width: 100%;
      height: 100%;
    }

    .login-box {
      background-color: #5f5c5cd8;
      margin: 0 auto;
      padding: 28px 24px;
      border-radius: 15px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
      text-align: center;
      width: 100%;
      max-width: 400px;
      min-width: 280px;
    }

    .logo {
      width: 80px;
      margin-bottom: 20px;
    }

    h2 {
      font-size: 1.5rem;
      margin-bottom: 30px;
      line-height: 1.2;
      color: #ffffff;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 10px;
      width: 100%;
      margin: 0 auto;
      /* Remove max-width restriction */
    }

    form>div {
      display: flex;
      flex-direction: column;
      align-items: stretch;
      width: 100%;
    }

    label {
      color: #ffffff;
      text-align: left;
      font-size: 0.9rem;
      margin-bottom: 5px;
      margin-left: 2px;
    }

    input {
      padding: 10px 12px;
      border: none;
      border-radius: 5px;
      font-size: 1rem;
      margin-bottom: 10px;
      background: #fff;
      color: #333;
      outline: none;
      transition: box-shadow 0.2s;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    input:focus {
      box-shadow: 0 0 0 2px #b80000;
    }

    button {
      padding: 10px;
      font-size: 0.97rem;
      margin-top: 6px;
      border: none;
      background-color: #d60000;
      color: white;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.3s ease;
      width: 100%;
    }

    button:hover {
      background-color: #b30000;
    }

    .register {
      margin-top: 10px;
      font-size: 0.85rem;
      color: #ffffff;
    }

    .register a {
      color: #FFCC33;
      text-decoration: none;
    }

    .register a:hover {
      text-decoration: underline;
    }

    .password-toggle {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      margin-top: 5px;
      margin-bottom: 10px;
    }

    .password-toggle input[type="checkbox"] {
      margin: 0 8px 0 0;
      width: auto;
      box-shadow: none;
    }

    .password-toggle label {
      margin: 0;
      font-size: 0.8rem;
      cursor: pointer;
    }

    /* Extra Small Devices (max-width: 575px) */
    @media (max-width: 575px) {
      .login-box {
        padding: 20px;
        width: 90vw;
        max-width: 90vw;
        min-width: unset;
        margin: 20px auto;
      }

      .logo {
        width: 50px;
      }

      h2 {
        font-size: 1.2rem;
      }

      form {
        gap: 8px;
        width: 100%;
      }

      input {
        padding: 10px 12px;
        font-size: 0.9rem;
      }

      button {
        font-size: 0.9rem;
        padding: 10px;
      }

      label {
        font-size: 0.85rem;
      }

      .password-toggle label {
        font-size: 0.75rem;
      }
    }

    /* Small Devices (min-width: 576px) and (max-width: 767px) */
    @media (min-width: 576px) and (max-width: 767px) {
      .login-box {
        padding: 25px;
        max-width: 400px;
        width: 85vw;
      }

      .logo {
        width: 60px;
      }

      h2 {
        font-size: 1.3rem;
      }

      input {
        padding: 11px 13px;
        font-size: 0.95rem;
      }

      button {
        font-size: 0.95rem;
        padding: 11px;
      }
    }

    /* Medium Devices (min-width: 768px) and (max-width: 991px) */
    @media (min-width: 768px) and (max-width: 991px) {
      .login-box {
        padding: 30px;
        max-width: 420px;
        width: 80vw;
      }

      .logo {
        width: 70px;
      }

      h2 {
        font-size: 1.4rem;
      }
    }

    /* Large Devices (min-width: 992px) and (max-width: 1199px) */
    @media (min-width: 992px) and (max-width: 1199px) {
      .login-box {
        padding: 35px;
        max-width: 450px;
        width: 75vw;
      }

      .logo {
        width: 75px;
      }

      h2 {
        font-size: 1.45rem;
      }
    }

    /* Extra Large Devices (min-width: 1200px) */
    @media (min-width: 1200px) {
      .login-box {
        padding: 40px;
        max-width: 500px;
        width: 70vw;
      }

      .logo {
        width: 80px;
      }

      h2 {
        font-size: 1.5rem;
      }
    }

    /* Custom Scrollbar Styles */
    ::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }

    ::-webkit-scrollbar-track {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
      background: rgba(255, 215, 0, 0.3);
      border-radius: 4px;
      transition: all 0.3s ease;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: rgba(255, 215, 0, 0.5);
    }

    ::-webkit-scrollbar-corner {
      background: rgba(255, 255, 255, 0.05);
    }

    /* Firefox Scrollbar */
    * {
      scrollbar-width: thin;
      scrollbar-color: rgba(255, 215, 0, 0.3) rgba(255, 255, 255, 0.05);
    }
  </style>
</head>

<body>

  <div class="container">
    <div class="login-box">
      <img src="../includes/images/mitsubishi_logo.png" alt="Mitsubishi Logo" class="logo" />
      <h2>MITSUBISHI<br />MOTORS</h2>
      <?php if (!empty($login_error)): ?>
        <div style="color:#ffd700;margin-bottom:10px;"><?php echo htmlspecialchars($login_error); ?></div>
      <?php endif; ?>
      <form method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
        <div>
          <label for="email">Email</label>
          <input type="email" id="email" name="email" placeholder="Enter your email" required autocomplete="username" />
        </div>
        <div>
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password" />
          <div class="password-toggle">
            <input type="checkbox" id="showPassword" onchange="togglePassword('password', this)">
            <label for="showPassword">Show password</label>
          </div>
        </div>
        <button type="submit">Log In</button>
        <button type="button" style="background:#ffd700;color:#b80000;font-weight:bold;padding:12px 0;width:100%;border-radius:8px;border:none;cursor:pointer;font-size:1rem;" onclick="window.location.href='landingpage.php'">
          Return to Landing Page
        </button>
        <p class="register">
          Don't have an account? <a href="create_account.php">Create an account</a><br />
          <a href="forgot_password.php">Forgot Password?</a>
        </p>
      </form>
    </div>
  </div>

  <script>
    function togglePassword(inputId, checkbox) {
      const passwordInput = document.getElementById(inputId);
      if (checkbox.checked) {
        passwordInput.type = 'text';
      } else {
        passwordInput.type = 'password';
      }
    }
  </script>
  

</body>

</html>