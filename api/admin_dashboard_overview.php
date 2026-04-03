<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Ensure required tables exist.
$conn->query("CREATE TABLE IF NOT EXISTS sit_in_history (
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
)");

$conn->query("CREATE TABLE IF NOT EXISTS feedback_reports (
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
)");

// Backward compatibility.
$hasSitInIdColumn = $conn->query("SHOW COLUMNS FROM feedback_reports LIKE 'sit_in_id'");
if ($hasSitInIdColumn && $hasSitInIdColumn->num_rows === 0) {
    $conn->query("ALTER TABLE feedback_reports ADD COLUMN sit_in_id INT NULL AFTER id");
    $conn->query("ALTER TABLE feedback_reports ADD INDEX idx_feedback_sitin (sit_in_id)");
}

$overview = [
    'total_students' => 0,
    'active_sessions' => 0,
    'today_sitins' => 0,
    'feedback_count' => 0,
    'avg_session' => '0m',
    'completed_count' => 0,
    'top_lab' => '--',
    'violations' => 0,
    'monthly_sessions' => array_fill(0, 12, 0),
    'recent_sessions' => []
];

$row = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc();
$overview['total_students'] = (int) ($row['c'] ?? 0);

$row = $conn->query("SELECT COUNT(*) AS c FROM sit_in_history WHERE (LOWER(status)='active' OR LOWER(status)='ongoing') AND time_out IS NULL")->fetch_assoc();
$overview['active_sessions'] = (int) ($row['c'] ?? 0);

$row = $conn->query("SELECT COUNT(*) AS c FROM sit_in_history WHERE DATE(time_in) = CURDATE()")->fetch_assoc();
$overview['today_sitins'] = (int) ($row['c'] ?? 0);

$row = $conn->query("SELECT COUNT(*) AS c FROM feedback_reports")->fetch_assoc();
$overview['feedback_count'] = (int) ($row['c'] ?? 0);

$row = $conn->query("SELECT COUNT(*) AS c FROM sit_in_history WHERE LOWER(status) IN ('completed','successful','success')")->fetch_assoc();
$overview['completed_count'] = (int) ($row['c'] ?? 0);

$row = $conn->query("SELECT laboratory, COUNT(*) AS c FROM sit_in_history GROUP BY laboratory ORDER BY c DESC LIMIT 1")->fetch_assoc();
$overview['top_lab'] = $row ? (string) $row['laboratory'] : '--';

$row = $conn->query("
    SELECT AVG(TIMESTAMPDIFF(MINUTE, time_in, time_out)) AS avg_min
    FROM sit_in_history
    WHERE time_out IS NOT NULL
")->fetch_assoc();
$avgMin = (int) round((float) ($row['avg_min'] ?? 0));
if ($avgMin >= 60) {
    $overview['avg_session'] = floor($avgMin / 60) . 'h ' . ($avgMin % 60) . 'm';
} else {
    $overview['avg_session'] = $avgMin . 'm';
}

// Keep violations as optional metric for now (placeholder from reports with empty message).
$row = $conn->query("SELECT COUNT(*) AS c FROM feedback_reports WHERE TRIM(message) = ''")->fetch_assoc();
$overview['violations'] = (int) ($row['c'] ?? 0);

$monthlyRows = $conn->query("
    SELECT MONTH(time_in) AS m, COUNT(*) AS c
    FROM sit_in_history
    WHERE YEAR(time_in) = YEAR(CURDATE())
    GROUP BY MONTH(time_in)
");
if ($monthlyRows) {
    while ($r = $monthlyRows->fetch_assoc()) {
        $index = (int) $r['m'] - 1;
        if ($index >= 0 && $index < 12) {
            $overview['monthly_sessions'][$index] = (int) $r['c'];
        }
    }
}

$recentStmt = $conn->prepare("
    SELECT
      s.id AS sit_in_id,
      u.student_id,
      CONCAT(u.first_name, ' ', COALESCE(NULLIF(u.middle_name, ''), ''), IF(u.middle_name IS NULL OR u.middle_name = '', '', ' '), u.last_name) AS name,
      s.laboratory,
      DATE_FORMAT(s.time_in, '%b %d, %h:%i %p') AS time_in,
      s.purpose,
      CASE WHEN (LOWER(s.status)='active' OR LOWER(s.status)='ongoing') AND s.time_out IS NULL THEN 'Active' ELSE 'Completed' END AS status
    FROM sit_in_history s
    INNER JOIN users u ON u.id = s.user_id
    ORDER BY s.time_in DESC
    LIMIT 8
");
$recentStmt->execute();
$recentResult = $recentStmt->get_result();
while ($r = $recentResult->fetch_assoc()) {
    $overview['recent_sessions'][] = $r;
}

echo json_encode(['success' => true, 'data' => $overview]);
?>
