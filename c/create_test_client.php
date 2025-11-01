<?php
/* c/create_test_client.php - Temporary file to create a test client */
require_once __DIR__ . '/../config.php';

// Create a test client
$username = 'testclient';
$password = password_hash('password123', PASSWORD_DEFAULT);
$name = 'Test Client';
$email = 'test@client.com';
$phone = '1234567890';

// Check if client already exists
$checkSQL = "SELECT id FROM clients WHERE username = ?";
$checkStmt = $conn->prepare($checkSQL);
$checkStmt->bind_param("s", $username);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows > 0) {
    echo "Test client already exists!<br>";
    echo "Username: testclient<br>";
    echo "Password: password123<br>";
} else {
    // Insert test client
    $insertSQL = "INSERT INTO clients (username, password, name, email, phone) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertSQL);
    $stmt->bind_param("sssss", $username, $password, $name, $email, $phone);
    
    if ($stmt->execute()) {
        echo "Test client created successfully!<br>";
        echo "Username: testclient<br>";
        echo "Password: password123<br>";
        echo "<br><a href='login.php'>Go to Client Login</a>";
    } else {
        echo "Error creating test client: " . $conn->error;
    }
    
    $stmt->close();
}

$checkStmt->close();
$conn->close();
?>