<?php
include_once(__DIR__ . '/db_conn.php'); // Adjust path if db_conn.php is elsewhere

$accountSeeds = [
  [
    'Username' => "admin",
    'Email' => "admin@mitsubishi.com",
    'Password' => "admin123", // Plain password, will be hashed
    'Role' => "Admin",
    'FirstName' => "System",
    'LastName' => "Administrator",
    'DateOfBirth' => "1980-01-01",
  ],
  [
    'Username' => "agent01",
    'Email' => "agent01@mitsubishi.com",
    'Password' => "agentpass",
    'Role' => "SalesAgent",
    'FirstName' => "John",
    'LastName' => "Smith",
    'DateOfBirth' => "1992-08-15",
  ],
  [
    'Username' => "customer01",
    'Email' => "customer01@email.com",
    'Password' => "customerpass",
    'Role' => "Customer",
    'FirstName' => "Alice",
    'LastName' => "Lee",
    'DateOfBirth' => "1995-12-20",
  ]
];

foreach ($accountSeeds as $seed) {
    // Check if account already exists
    $stmt = $connect->prepare("SELECT Id FROM accounts WHERE Email = ? OR Username = ?");
    $stmt->execute([$seed['Email'], $seed['Username']]);
    if ($stmt->fetch()) {
        echo "Account with email {$seed['Email']} or username {$seed['Username']} already exists. Skipping.<br>";
        continue;
    }

    $passwordHash = password_hash($seed['Password'], PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO accounts (Username, Email, PasswordHash, Role, FirstName, LastName, DateOfBirth, CreatedAt, UpdatedAt) 
            VALUES (:Username, :Email, :PasswordHash, :Role, :FirstName, :LastName, :DateOfBirth, NOW(), NOW())";
    
    $stmt = $connect->prepare($sql);
    
    try {
        $stmt->execute([
            ':Username' => $seed['Username'],
            ':Email' => $seed['Email'],
            ':PasswordHash' => $passwordHash,
            ':Role' => $seed['Role'],
            ':FirstName' => $seed['FirstName'],
            ':LastName' => $seed['LastName'],
            ':DateOfBirth' => $seed['DateOfBirth']
        ]);
        echo "Successfully inserted {$seed['Username']}<br>";
    } catch (PDOException $e) {
        echo "Error inserting {$seed['Username']}: " . $e->getMessage() . "<br>";
    }
}

echo "Seeding complete.<br>";
?>
