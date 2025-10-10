<?php
session_start();
include_once(dirname(__DIR__) . '/includes/database/db_conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'Customer';
    $firstName = $_POST['first_name'] ?? null;
    $lastName = $_POST['last_name'] ?? null;
    $dob = $_POST['dob'] ?? null;

    // Hash the password for security
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Check for existing username or email
    $stmt = $connect->prepare("SELECT 1 FROM accounts WHERE Username = ? OR Email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        echo "Account already exists!";
        exit;
    }

    // Insert new account
    $stmt = $connect->prepare("INSERT INTO accounts (Username, Email, PasswordHash, Role, FirstName, LastName, DateOfBirth, CreatedAt, UpdatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    if ($stmt->execute([$username, $email, $passwordHash, $role, $firstName, $lastName, $dob])) {
        // Get the newly created user ID
        $newUserId = $connect->lastInsertId();
        // Send notifications to agents and admins
        require_once(dirname(__DIR__) . '/includes/api/notification_api.php');
        $notifTitle = 'New Account Registered';
        $notifMsg = "A new user has registered: $username ($role).";
        createNotification(null, 'Admin', $notifTitle, $notifMsg, 'account', $newUserId);
        createNotification(null, 'SalesAgent', $notifTitle, $notifMsg, 'account', $newUserId);
        // Automatically log in the user if they're a Customer
        if ($role === 'Customer') {
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['user_role'] = $role;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            // Redirect to verification page
            header("Location: verification.php");
            exit;
        } else {
            echo "Account created successfully!";
        }
    } else {
        echo "Failed to create account!";
    }
}
?>
