<?php
require_once 'db.php';

$username = 'admin';
$password = password_hash('admin123', PASSWORD_DEFAULT);

// Check if admin exists
$check_stmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
$check_stmt->bind_param("s", $username);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // Admin exists, update password
    $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $password, $username);
    if ($stmt->execute()) {
        echo "Admin password updated!<br>";
        echo "Username: admin<br>";
        echo "Password: admin123";
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    // Admin doesn't exist, create new
    $stmt = $conn->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $password);
    if ($stmt->execute()) {
        echo "Admin account created!<br>";
        echo "Username: admin<br>";
        echo "Password: admin123";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
