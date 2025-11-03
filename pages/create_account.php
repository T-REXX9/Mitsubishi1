<?php
session_start();
include_once(dirname(__DIR__) . '/includes/database/db_conn.php');

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}

$register_error = '';
$register_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Validate CSRF token
  $token = $_POST['csrf_token'] ?? '';
  if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    $register_error = "Security validation failed. Please try again.";
  } else {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Email must end with @gmail.com
    if (!str_ends_with($email, '@gmail.com')) {
      $register_error = "Email must end with @gmail.com.";
    }
    // Password complexity
    elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
      $register_error = "Password must have at least 8 characters, include uppercase, lowercase, number, and special character.";
    }
    // Password match check
    elseif ($password !== $confirm_password) {
      $register_error = "Passwords do not match.";
    } else {
      // Split name
      $nameParts = explode(' ', $name, 2);
      $firstName = $nameParts[0] ?? '';
      $lastName = $nameParts[1] ?? '';
      $username = explode('@', $email)[0];

      // Check duplicates
      $stmt = $connect->prepare("SELECT COUNT(*) FROM accounts WHERE Email = ? OR Username = ?");
      $stmt->execute([$email, $username]);
      if ($stmt->fetchColumn() > 0) {
        $register_error = "Email or username already exists.";
      } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO accounts (Username, Email, PasswordHash, Role, FirstName, LastName, email_verified, CreatedAt, UpdatedAt)
                VALUES (?, ?, ?, 'Customer', ?, ?, 0, NOW(), NOW())";
        $stmt = $connect->prepare($sql);
        if ($stmt->execute([$username, $email, $passwordHash, $firstName, $lastName])) {
          $newUserId = $connect->lastInsertId();
          require_once(dirname(__DIR__) . '/includes/services/OTPService.php');
          $otpService = new \Mitsubishi\Services\OTPService($connect);
          $otpResult = $otpService->sendOTP($newUserId, $email);

          if ($otpResult['success']) {
            $_SESSION['pending_verification_user_id'] = $newUserId;
            $_SESSION['pending_verification_email'] = $email;
            header("Location: verify_otp.php");
            exit;
          } else {
            $register_error = "Account created but failed to send verification email. Please contact support.";
          }
        } else {
          $register_error = "Failed to create account. Please try again.";
        }
      }
    }
  }

  // Regenerate CSRF token
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
<title>Create Account - Mitsubishi Motors</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', sans-serif; }
  html, body { height:100%; width:100%; margin:0; padding:0; overflow:hidden; }
  body {
    background-image: url(../includes/images/tritonbg.jpg);
    background-color: #DC143C1A;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
  }
  .container {
    display:flex; justify-content:center; align-items:center;
    width:100%; height:100%;
  }
  .login-box {
    background-color:#5f5c5cb0; margin:0 auto; padding:28px 24px;
    border-radius:15px; box-shadow:0 0 20px rgba(0,0,0,0.5);
    text-align:center; max-width:700px; min-width:700px; max-height:950px;
  }
  .logo { width:80px; margin-bottom:20px; }
  h2 { color:#fff; font-size:1.5rem; margin-bottom:30px; }
  form>div { display:flex; flex-direction:column; align-items:stretch; width:100%; }
  label { color:#fff; text-align:left; font-size:0.9rem; margin-bottom:5px; margin-left:2px; }
  input {
    padding:10px 12px; border:none; border-radius:5px; font-size:1rem;
    margin-bottom:10px; background:#fff; color:#333; outline:none;
    transition:box-shadow 0.2s; box-shadow:0 1px 2px rgba(0,0,0,0.05);
  }
  input:focus { box-shadow:0 0 0 2px #b80000; }
  button {
    padding:10px; font-size:0.97rem; margin-top:6px; border:none;
    background-color:#d60000; color:white; border-radius:8px;
    font-weight:bold; cursor:pointer; transition:background-color 0.3s ease;
    width:100%;
  }
  button:hover { background-color:#b30000; }
  .register { color:#fff; margin-top:10px; font-size:0.85rem; }
  .register a { color:#ffd700; text-decoration:none; }
  .register a:hover { text-decoration:underline; }
  .password-toggle {
    display:flex; align-items:center; justify-content:flex-start;
    margin-top:5px; margin-bottom:10px;
  }
  .password-toggle input[type="checkbox"] {
    margin:0 8px 0 0; width:auto; box-shadow:none;
  }
  .password-toggle label {
    margin:0; font-size:0.8rem; cursor:pointer; color:#ffffff;
  }
</style>
</head>
<body>
<div class="container">
  <div class="login-box">
    <img src="../includes/images/mitsubishi_logo.png" alt="Mitsubishi Logo" class="logo" />
    <h2>Create Your Account</h2>
    <?php if (!empty($register_error)): ?>
      <div style="color:#ffd700;margin-bottom:10px;"><?php echo htmlspecialchars($register_error); ?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off" onsubmit="return validateForm()">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
      <div>
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" placeholder="Enter your full name" 
               onkeypress="return restrictNumbers(event)" required />
      </div>
      <div>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Enter your email" required />
      </div>
      <div>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Create a password" required />
      </div>
      <div>
        <label for="confirm_password">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required />
        <div class="password-toggle">
          <input type="checkbox" id="showPasswords" onchange="togglePasswords(this)">
          <label for="showPasswords">Show passwords</label>
        </div>
      </div>
      <button type="submit">Create Account</button>
      <button type="button" style="background:#ffd700;color:#b80000;font-weight:bold;padding:12px 0;width:100%;border-radius:8px;border:none;cursor:pointer;font-size:1rem;" onclick="window.location.href='landingpage.php';return false;">
        Return to Landing Page
      </button>
      <p class="register">Already have an account? <a href="login.php">Log In</a></p>
    </form>
  </div>
</div>

<script>
function restrictNumbers(event) {
  const char = String.fromCharCode(event.which);
  if (/[0-9]/.test(char)) {
    event.preventDefault();
    return false;
  }
  return true;
}

function togglePasswords(checkbox) {
  const password = document.getElementById("password");
  const confirmPassword = document.getElementById("confirm_password");
  const type = checkbox.checked ? "text" : "password";
  password.type = type;
  confirmPassword.type = type;
}

function validateForm() {
  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value;
  const confirmPassword = document.getElementById("confirm_password").value;

  if (!email.endsWith("@gmail.com")) {
    alert("Email must end with @gmail.com");
    return false;
  }

  const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
  if (!passwordPattern.test(password)) {
    alert("Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.");
    return false;
  }

  if (password !== confirmPassword) {
    alert("Passwords do not match.");
    return false;
  }

  return true;
}
</script>
</body>
</html>
