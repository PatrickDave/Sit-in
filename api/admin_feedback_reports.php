<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Ensure feedback reports table exists.
$createTableSql = "CREATE TABLE IF NOT EXISTS feedback_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    laboratory VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    report_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_feedback_date (report_date),
    INDEX idx_feedback_user (user_id),
    CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$conn->query($createTableSql);

function fetch_feedback_reports($conn, $search) {
    $sql = "SELECT
                u.student_id AS id_number,
                f.laboratory,
                DATE_FORMAT(f.report_date, '%Y-%m-%d') AS report_date,
                f.message
            FROM feedback_reports f
            INNER JOIN users u ON u.id = f.user_id";

    $params = [];
    $types = '';
    if ($search !== '') {
        $sql .= " WHERE u.student_id LIKE ? OR f.laboratory LIKE ? OR f.message LIKE ? OR DATE_FORMAT(f.report_date, '%Y-%m-%d') LIKE ?";
        $like = '%' . $search . '%';
        $params = [$like, $like, $like, $like];
        $types = 'ssss';
    }

    $sql .= " ORDER BY f.report_date DESC, f.id DESC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

$action = $_GET['action'] ?? 'list';
$search = trim($_GET['search'] ?? '');

if ($action === 'excel') {
    $rows = fetch_feedback_reports($conn, $search);
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="feedback_reports_' . date('Ymd_His') . '.xls"');

    echo "ID NUMBER\tLABORATORY\tDATE\tMESSAGE\n";
    foreach ($rows as $row) {
        echo $row['id_number'] . "\t" .
             $row['laboratory'] . "\t" .
             $row['report_date'] . "\t" .
             preg_replace('/\s+/', ' ', trim((string) $row['message'])) . "\n";
    }
    exit();
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => fetch_feedback_reports($conn, $search)
]);
?>
