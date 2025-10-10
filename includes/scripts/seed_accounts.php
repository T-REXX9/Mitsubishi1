<?php
// This script should be run once to populate the accounts table.
// Ensure this script is protected or removed after use in a production environment.

require_once dirname(__DIR__) . '/pages/database/db_conn.php'; // Adjust path as necessary

$accountSeeds = [
    [
        'Username' => "admin",
        'Email' => "admin@gmail.com",
        'PasswordPlain' => "admin123", // Plain password, will be hashed
        'Role' => "Admin",
        'FirstName' => "System",
        'LastName' => "Administrator",
        'ProfileImage' => null,
        'DateOfBirth' => "1980-01-01",
    ],
    [
        'Username' => "agent01",
        'Email' => "agent01@gmail.com",
        'PasswordPlain' => "agentpass",
        'Role' => "SalesAgent",
        'FirstName' => "John",
        'LastName' => "Smith",
        'ProfileImage' => null,
        'DateOfBirth' => "1992-08-15",
    ],
    [
        'Username' => "customer01",
        'Email' => "customer01@email.com",
        'PasswordPlain' => "customerpass",
        'Role' => "Customer",
        'FirstName' => "Alice",
        'LastName' => "Lee",
        'ProfileImage' => null,
        'DateOfBirth' => "1995-12-20",
    ]
];

echo "Starting account seeding...<br>";

foreach ($accountSeeds as $seed) {
    try {
        // Check if email already exists
        $stmtCheck = $connect->prepare("SELECT Id FROM accounts WHERE Email = ?");
        $stmtCheck->execute([$seed['Email']]);
        if ($stmtCheck->fetch()) {
            echo "Account with email " . htmlspecialchars($seed['Email']) . " already exists. Skipping.<br>";
            continue;
        }

        // Hash the password
        $passwordHash = password_hash($seed['PasswordPlain'], PASSWORD_DEFAULT);

        // Prepare SQL statement
        // Assuming your table has columns: Username, Email, PasswordHash, Role, FirstName, LastName, ProfileImage, DateOfBirth, CreatedAt, UpdatedAt
        // Id is assumed to be AUTO_INCREMENT. LastLoginAt can be NULL by default.
        $sql = "INSERT INTO accounts (Username, Email, PasswordHash, Role, FirstName, LastName, ProfileImage, DateOfBirth, CreatedAt, UpdatedAt) 
                VALUES (:Username, :Email, :PasswordHash, :Role, :FirstName, :LastName, :ProfileImage, :DateOfBirth, NOW(), NOW())";
        
        $stmt = $connect->prepare($sql);
        
        $stmt->bindParam(':Username', $seed['Username']);
        $stmt->bindParam(':Email', $seed['Email']);
        $stmt->bindParam(':PasswordHash', $passwordHash);
        $stmt->bindParam(':Role', $seed['Role']);
        $stmt->bindParam(':FirstName', $seed['FirstName']);
        $stmt->bindParam(':LastName', $seed['LastName']);
        $stmt->bindParam(':ProfileImage', $seed['ProfileImage']); // PDO handles null correctly
        $stmt->bindParam(':DateOfBirth', $seed['DateOfBirth']);
        
        $stmt->execute();
        echo "Successfully inserted account for " . htmlspecialchars($seed['Email']) . ".<br>";

    } catch (PDOException $e) {
        echo "Error inserting account for " . htmlspecialchars($seed['Email']) . ": " . $e->getMessage() . "<br>";
    }
}

echo "Account seeding finished.<br>";

?>
