<?php
require_once 'db.php';

$username = 'admin';
$password = password_hash('admin123', PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE admins SET password = ? WHERE username = ?");
$stmt->bind_param("ss", $password, $username);

if ($stmt->execute()) {
    echo "Admin password reset!<br>";
    echo "Username: admin<br>";
    echo "Password: admin123";
} else {
    echo "Error: " . $conn->error;
}
?>
