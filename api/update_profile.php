<?php
session_start();
require_once 'db.php';

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../html/login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $address = $_POST['address'];

    // 2. Handle Profile Picture Upload
    $profile_picture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
        $fileName = $_FILES['profile_picture']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Generate a unique name to prevent overwriting
        $newFileName = "user_" . $user_id . "_" . time() . "." . $fileExtension;
        $uploadFileDir = '../image/';
        $dest_path = $uploadFileDir . $newFileName;

        // Move the file to your images folder
        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $profile_picture = $newFileName;
        }
    }

    // 3. Build the SQL Query
    if ($profile_picture) {
        // Update including new photo
        $sql = "UPDATE users SET first_name=?, middle_name=?, last_name=?, email=?, address=?, profile_picture=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $first_name, $middle_name, $last_name, $email, $address, $profile_picture, $user_id);
    } else {
        // Update text fields only
        $sql = "UPDATE users SET first_name=?, middle_name=?, last_name=?, email=?, address=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $first_name, $middle_name, $last_name, $email, $address, $user_id);
    }

    // 4. Execute and Redirect
    if ($stmt->execute()) {
        // Redirect back to dashboard with success message
        header("Location: ../html/student/studentDashboard.html?update=success");
    } else {
        echo "Error updating profile: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
}