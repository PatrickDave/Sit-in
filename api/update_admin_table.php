<?php
require_once 'db.php';

// Add columns if they don't exist
$sql = "ALTER TABLE admins ADD COLUMN IF NOT EXISTS name VARCHAR(100) DEFAULT NULL";
$conn->query($sql);

$sql = "ALTER TABLE admins ADD COLUMN IF NOT EXISTS email VARCHAR(100) DEFAULT NULL";
$conn->query($sql);

$sql = "ALTER TABLE admins ADD COLUMN IF NOT EXISTS profile_image TEXT DEFAULT NULL";
$conn->query($sql);

// Update existing record
$stmt = $conn->prepare("UPDATE admins SET name = 'Admin User', email = 'admin@ccs.edu' WHERE username = 'admin'");
$stmt->execute();

echo "Database updated successfully!";
?>
