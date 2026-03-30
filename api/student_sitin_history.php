<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Ensure sit-in history table exists.
$createTableSql = "CREATE TABLE IF NOT EXISTS sit_in_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    laboratory VARCHAR(100) NOT NULL,
    purpose VARCHAR(255) NOT NULL,
    time_in DATETIME NOT NULL,
    time_out DATETIME DEFAULT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'successful',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_status_time (user_id, status, time_in),
    CONSTRAINT fk_sit_in_history_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (!$conn->query($createTableSql)) {
    echo json_encode(['success' => false, 'message' => 'Failed to initialize sit-in history table']);
    exit();
}

$stmt = $conn->prepare(
    "SELECT
        u.student_id AS id_number,
        CONCAT(u.first_name, ' ', COALESCE(NULLIF(u.middle_name, ''), ''), IF(u.middle_name IS NULL OR u.middle_name = '', '', ' '), u.last_name) AS name,
        purpose,
        laboratory,
        DATE_FORMAT(time_in, '%h:%i %p') AS time_in,
        IFNULL(DATE_FORMAT(time_out, '%h:%i %p'), '--') AS time_out,
        DATE_FORMAT(time_in, '%Y-%m-%d') AS date
     FROM sit_in_history s
     INNER JOIN users u ON u.id = s.user_id
     WHERE s.user_id = ? AND LOWER(s.status) IN ('success', 'successful', 'completed')
     ORDER BY s.time_in DESC"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => $history
]);
?>
