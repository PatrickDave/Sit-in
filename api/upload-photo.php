<?php
// Photo Upload API
// POST /api/upload-photo.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

include 'db-config.php';

try {
    // Validate session
    $userId = validateSession();

    // Check if file was uploaded
    if (!isset($_FILES['photo'])) {
        sendError('No photo file provided', 400);
    }

    $file = $_FILES['photo'];

    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        sendError('File upload failed', 400);
    }

    // File size validation (5MB max)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        sendError('File size must be less than 5MB', 400);
    }

    // File type validation
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        sendError('Only JPG, PNG, and GIF files are allowed', 400);
    }

    // Create unique filename
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFilename = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
    $uploadPath = UPLOADS_PATH . '/' . $newFilename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        sendError('Failed to save photo', 500);
    }

    // Delete old photo if exists
    $stmt = $pdo->prepare("SELECT profile_photo_path FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $oldUser = $stmt->fetch();

    if ($oldUser && $oldUser['profile_photo_path']) {
        $oldPath = str_replace('\\', '/', $oldUser['profile_photo_path']);
        if (file_exists($oldPath . '.php')) {
            @unlink($oldPath);
        }
    }

    // Update user profile with new photo path
    $photoPath = '/Sit-in/uploads/' . $newFilename;
    $stmt = $pdo->prepare("UPDATE users SET profile_photo_path = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$photoPath, $userId]);

    sendSuccess('Photo uploaded successfully', [
        'photoPath' => $photoPath,
        'filename' => $newFilename
    ]);

} catch (Exception $e) {
    error_log('Photo upload error: ' . $e->getMessage());
    sendError('Photo upload failed', 500);
}

?>
