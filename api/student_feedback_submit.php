<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$sit_in_id = (int) ($_POST['sit_in_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if ($sit_in_id <= 0 || $message === '') {
    echo json_encode(['success' => false, 'message' => 'Please provide sit-in ID and feedback message.']);
    exit();
}

if (strlen($message) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Feedback message is too long.']);
    exit();
}

// Ensure feedback table exists.
$createFeedbackTableSql = "CREATE TABLE IF NOT EXISTS feedback_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sit_in_id INT NULL,
    user_id INT NOT NULL,
    laboratory VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    report_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_feedback_date (report_date),
    INDEX idx_feedback_user (user_id),
    INDEX idx_feedback_sitin (sit_in_id),
    CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$conn->query($createFeedbackTableSql);

// Backward compatibility if table existed before sit_in_id was introduced.
$hasSitInIdColumn = $conn->query("SHOW COLUMNS FROM feedback_reports LIKE 'sit_in_id'");
if ($hasSitInIdColumn && $hasSitInIdColumn->num_rows === 0) {
    $conn->query("ALTER TABLE feedback_reports ADD COLUMN sit_in_id INT NULL AFTER id");
    $conn->query("ALTER TABLE feedback_reports ADD INDEX idx_feedback_sitin (sit_in_id)");
}

// Validate sit-in record ownership and status.
$sitStmt = $conn->prepare(
    "SELECT id, user_id, laboratory, time_in, time_out, status
     FROM sit_in_history
     WHERE id = ? AND user_id = ?
     LIMIT 1"
);
$sitStmt->bind_param("ii", $sit_in_id, $user_id);
$sitStmt->execute();
$sitRecord = $sitStmt->get_result()->fetch_assoc();

if (!$sitRecord) {
    echo json_encode(['success' => false, 'message' => 'Sit-in record not found.']);
    exit();
}

// Feedback is now allowed even without logout/completion.

// One feedback per sit-in/user.
$checkStmt = $conn->prepare("SELECT id FROM feedback_reports WHERE sit_in_id = ? AND user_id = ? LIMIT 1");
$checkStmt->bind_param("ii", $sit_in_id, $user_id);
$checkStmt->execute();
if ($checkStmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Feedback already submitted for this session.']);
    exit();
}

$laboratory = trim((string) $sitRecord['laboratory']);
$reportDate = !empty($sitRecord['time_out']) ? date('Y-m-d', strtotime($sitRecord['time_out'])) : date('Y-m-d', strtotime($sitRecord['time_in']));

$insertStmt = $conn->prepare(
    "INSERT INTO feedback_reports (sit_in_id, user_id, laboratory, message, report_date)
     VALUES (?, ?, ?, ?, ?)"
);
$insertStmt->bind_param("iisss", $sit_in_id, $user_id, $laboratory, $message, $reportDate);

if (!$insertStmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to submit feedback.']);
    exit();
}

echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully.']);
?>
