<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

function fetch_reports($conn, $search, $report_date) {
    $sql = "SELECT
                u.student_id AS id_number,
                CONCAT(u.first_name, ' ', COALESCE(NULLIF(u.middle_name, ''), ''), IF(u.middle_name IS NULL OR u.middle_name = '', '', ' '), u.last_name) AS name,
                s.purpose,
                s.laboratory,
                DATE_FORMAT(s.time_in, '%h:%i:%s %p') AS login_time,
                IFNULL(DATE_FORMAT(s.time_out, '%h:%i:%s %p'), '--') AS logout_time,
                DATE_FORMAT(s.time_in, '%Y-%m-%d') AS report_date
            FROM sit_in_history s
            INNER JOIN users u ON u.id = s.user_id";

    $where = [];
    $params = [];
    $types = '';

    if ($search !== '') {
        $where[] = "(u.student_id LIKE ? OR u.first_name LIKE ? OR u.middle_name LIKE ? OR u.last_name LIKE ? OR s.purpose LIKE ? OR s.laboratory LIKE ?)";
        $like = '%' . $search . '%';
        for ($i = 0; $i < 6; $i++) {
            $params[] = $like;
            $types .= 's';
        }
    }

    if ($report_date !== '') {
        $where[] = "DATE(s.time_in) = ?";
        $params[] = $report_date;
        $types .= 's';
    }

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY s.time_in DESC";
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
$report_date = trim($_GET['report_date'] ?? '');

if ($action === 'excel') {
    $rows = fetch_reports($conn, $search, $report_date);

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="sitin_reports_' . date('Ymd_His') . '.xls"');

    echo "ID Number\tName\tPurpose\tLaboratory\tLogin\tLogout\tDate\n";
    foreach ($rows as $row) {
        echo $row['id_number'] . "\t" .
             $row['name'] . "\t" .
             $row['purpose'] . "\t" .
             $row['laboratory'] . "\t" .
             $row['login_time'] . "\t" .
             $row['logout_time'] . "\t" .
             $row['report_date'] . "\n";
    }
    exit();
}

header('Content-Type: application/json');
$rows = fetch_reports($conn, $search, $report_date);
echo json_encode(['success' => true, 'data' => $rows]);
?>
