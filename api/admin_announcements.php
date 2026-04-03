<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

$createAnnouncementsSql = "CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message TEXT NOT NULL,
    posted_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ann_created (created_at)
)";
$conn->query($createAnnouncementsSql);

if ($action === 'create') {
    if (!isset($_SESSION['admin_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    $message = trim($_POST['message'] ?? '');
    if ($message === '') {
        echo json_encode(['success' => false, 'message' => 'Announcement message is required.']);
        exit();
    }
    if (strlen($message) > 1000) {
        echo json_encode(['success' => false, 'message' => 'Announcement message is too long.']);
        exit();
    }

    $postedBy = (int) $_SESSION['admin_id'];
    $stmt = $conn->prepare("INSERT INTO announcements (message, posted_by) VALUES (?, ?)");
    $stmt->bind_param("si", $message, $postedBy);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to post announcement.']);
        exit();
    }
    echo json_encode(['success' => true, 'message' => 'Announcement posted successfully.']);
    exit();
}

if ($action === 'update') {
    if (!isset($_SESSION['admin_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    $id = (int) ($_POST['id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    if ($id <= 0 || $message === '') {
        echo json_encode(['success' => false, 'message' => 'Announcement ID and message are required.']);
        exit();
    }
    if (strlen($message) > 1000) {
        echo json_encode(['success' => false, 'message' => 'Announcement message is too long.']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE announcements SET message = ? WHERE id = ?");
    $stmt->bind_param("si", $message, $id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to update announcement.']);
        exit();
    }

    if ($stmt->affected_rows < 0) {
        echo json_encode(['success' => false, 'message' => 'Announcement not found.']);
        exit();
    }

    echo json_encode(['success' => true, 'message' => 'Announcement updated successfully.']);
    exit();
}

if ($action === 'delete') {
    if (!isset($_SESSION['admin_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Announcement ID is required.']);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete announcement.']);
        exit();
    }

    echo json_encode(['success' => true, 'message' => 'Announcement deleted successfully.']);
    exit();
}

// list
// Allow both admin and logged-in students to read announcements.
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$limit = (int) ($_GET['limit'] ?? 8);
if ($limit < 1) $limit = 8;
if ($limit > 30) $limit = 30;

$stmt = $conn->prepare("
    SELECT id, message, DATE_FORMAT(created_at, '%b %d, %Y %h:%i %p') AS created_at
    FROM announcements
    ORDER BY created_at DESC
    LIMIT ?
");
$stmt->bind_param("i", $limit);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($r = $result->fetch_assoc()) {
    $rows[] = $r;
}

echo json_encode(['success' => true, 'data' => $rows]);
?>
