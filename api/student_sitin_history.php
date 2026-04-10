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
    reservation_id INT NULL,
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
$conn->query("CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    laboratory VARCHAR(20) NOT NULL,
    reservation_date DATE NOT NULL,
    time_in TIME NOT NULL,
    time_out TIME NOT NULL,
    pc_number INT NOT NULL,
    purpose VARCHAR(255) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    admin_note VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reservation_user (user_id),
    INDEX idx_reservation_status (status),
    INDEX idx_reservation_date (reservation_date),
    CONSTRAINT fk_reservations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");
$hasReservationIdColumn = $conn->query("SHOW COLUMNS FROM sit_in_history LIKE 'reservation_id'");
if ($hasReservationIdColumn && $hasReservationIdColumn->num_rows === 0) {
    $conn->query("ALTER TABLE sit_in_history ADD COLUMN reservation_id INT NULL AFTER user_id");
}

// Ensure feedback table exists (for feedback-submitted markers).
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

$stmt = $conn->prepare(
    "SELECT
        s.id AS sit_in_id,
        u.student_id AS id_number,
        CONCAT(u.first_name, ' ', COALESCE(NULLIF(u.middle_name, ''), ''), IF(u.middle_name IS NULL OR u.middle_name = '', '', ' '), u.last_name) AS name,
        COALESCE(r.purpose, s.purpose) AS purpose,
        s.laboratory,
        DATE_FORMAT(s.time_in, '%h:%i %p') AS time_in,
        IFNULL(DATE_FORMAT(s.time_out, '%h:%i %p'), '--') AS time_out,
        DATE_FORMAT(s.time_in, '%Y-%m-%d') AS date,
        s.status,
        CASE WHEN f.id IS NULL THEN 0 ELSE 1 END AS feedback_submitted
     FROM sit_in_history s
     INNER JOIN users u ON u.id = s.user_id
     LEFT JOIN reservations r ON r.id = s.reservation_id
     LEFT JOIN feedback_reports f ON f.sit_in_id = s.id AND f.user_id = s.user_id
     WHERE s.user_id = ?
       AND (
            s.time_out IS NOT NULL
            OR LOWER(s.status) IN ('completed', 'successful', 'success')
       )
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
