<?php
session_start();
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$admin_id = $_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Get current profile
    $stmt = $conn->prepare("SELECT id, username, name, email, profile_image FROM admins WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'data' => [
                'username' => $row['username'],
                'name' => $row['name'] ?? 'Admin User',
                'email' => $row['email'] ?? 'admin@ccs.edu',
                'profile_image' => $row['profile_image']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Admin not found']);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? 'Admin User';
    $email = $_POST['email'] ?? 'admin@ccs.edu';
    
    // Handle image upload
    $profile_image = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['profile_image']['tmp_name'];
        $file_name = $_FILES['profile_image']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Allowed extensions
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $allowed_ext)) {
            // Read image and convert to base64
            $image_data = base64_encode(file_get_contents($file_tmp));
            $profile_image = 'data:image/' . $file_ext . ';base64,' . $image_data;
        }
    }
    
    // Update profile
    if ($profile_image) {
        $stmt = $conn->prepare("UPDATE admins SET name = ?, email = ?, profile_image = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $email, $profile_image, $admin_id);
    } else {
        $stmt = $conn->prepare("UPDATE admins SET name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $email, $admin_id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating profile']);
    }
    exit();
}
?>
