<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$allowed_labs = ['524', '526', '530', '540', '544'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

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
$conn->query($createTableSql);

$createReservationsSql = "CREATE TABLE IF NOT EXISTS reservations (
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
)";
$conn->query($createReservationsSql);

$hasReservationIdColumn = $conn->query("SHOW COLUMNS FROM sit_in_history LIKE 'reservation_id'");
if ($hasReservationIdColumn && $hasReservationIdColumn->num_rows === 0) {
    $conn->query("ALTER TABLE sit_in_history ADD COLUMN reservation_id INT NULL AFTER user_id");
}

if ($method === 'GET' && $action === 'list') {
    $search = trim($_GET['search'] ?? '');

    $sql = "SELECT
                s.id AS sit_in_id,
                u.id AS user_id,
                u.student_id,
                CONCAT(u.first_name, ' ', COALESCE(NULLIF(u.middle_name, ''), ''), IF(u.middle_name IS NULL OR u.middle_name = '', '', ' '), u.last_name) AS name,
                COALESCE(r.purpose, s.purpose) AS purpose,
                s.laboratory AS sit_lab,
                u.sessions_remaining AS session_count,
                CASE
                    WHEN (LOWER(s.status) = 'active' OR LOWER(s.status) = 'ongoing') AND s.time_out IS NULL THEN 'Active'
                    ELSE 'Not Active'
                END AS status_label
            FROM sit_in_history s
            INNER JOIN users u ON u.id = s.user_id
            LEFT JOIN reservations r ON r.id = s.reservation_id";

    if ($search !== '') {
        $sql .= " WHERE u.student_id LIKE ? OR u.first_name LIKE ? OR u.middle_name LIKE ? OR u.last_name LIKE ? OR s.purpose LIKE ? OR s.laboratory LIKE ?";
    }
    $sql .= " ORDER BY s.time_in DESC";

    $stmt = $conn->prepare($sql);
    if ($search !== '') {
        $like = '%' . $search . '%';
        $stmt->bind_param("ssssss", $like, $like, $like, $like, $like, $like);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = [
            'sit_in_id' => (int) $row['sit_in_id'],
            'user_id' => (int) $row['user_id'],
            'student_id' => $row['student_id'],
            'name' => $row['name'],
            'purpose' => $row['purpose'],
            'sit_lab' => $row['sit_lab'],
            'session' => (int) $row['session_count'],
            'status' => $row['status_label'],
        ];
    }

    echo json_encode(['success' => true, 'data' => $records]);
    exit();
}

if ($method === 'POST' && $action === 'start') {
    $student_id = trim($_POST['student_id'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $sit_lab = trim($_POST['sit_lab'] ?? '');

    if ($student_id === '' || $purpose === '' || $sit_lab === '') {
        echo json_encode(['success' => false, 'message' => 'Please complete all required fields.']);
        exit();
    }

    if (!in_array($sit_lab, $allowed_labs, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid laboratory number.']);
        exit();
    }

    $userStmt = $conn->prepare("SELECT id, sessions_remaining FROM users WHERE student_id = ?");
    $userStmt->bind_param("s", $student_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult->fetch_assoc();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Student not found.']);
        exit();
    }

    if ((int) $user['sessions_remaining'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'This student has no remaining sessions.']);
        exit();
    }

    $user_id = (int) $user['id'];

    $activeCheck = $conn->prepare(
        "SELECT id FROM sit_in_history WHERE user_id = ? AND (LOWER(status) = 'active' OR LOWER(status) = 'ongoing') AND time_out IS NULL LIMIT 1"
    );
    $activeCheck->bind_param("i", $user_id);
    $activeCheck->execute();
    if ($activeCheck->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Student already has an active sit-in session.']);
        exit();
    }

    $insert = $conn->prepare(
        "INSERT INTO sit_in_history (user_id, laboratory, purpose, time_in, status) VALUES (?, ?, ?, NOW(), 'active')"
    );
    $insert->bind_param("iss", $user_id, $sit_lab, $purpose);
    if (!$insert->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to create sit-in record.']);
        exit();
    }

    echo json_encode(['success' => true, 'message' => 'Sit-in session started successfully.']);
    exit();
}

if ($method === 'POST' && $action === 'logout') {
    $sit_in_id = (int) ($_POST['sit_in_id'] ?? 0);
    if ($sit_in_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid sit-in ID.']);
        exit();
    }

    $recordStmt = $conn->prepare("SELECT id, user_id, status, time_out FROM sit_in_history WHERE id = ? LIMIT 1");
    $recordStmt->bind_param("i", $sit_in_id);
    $recordStmt->execute();
    $record = $recordStmt->get_result()->fetch_assoc();

    if (!$record) {
        echo json_encode(['success' => false, 'message' => 'Sit-in record not found.']);
        exit();
    }

    $isActive = in_array(strtolower((string) $record['status']), ['active', 'ongoing'], true) && empty($record['time_out']);
    if (!$isActive) {
        echo json_encode(['success' => false, 'message' => 'Session is already logged out.']);
        exit();
    }

    $conn->begin_transaction();
    try {
        $updateSitIn = $conn->prepare("UPDATE sit_in_history SET time_out = NOW(), status = 'completed' WHERE id = ?");
        $updateSitIn->bind_param("i", $sit_in_id);
        if (!$updateSitIn->execute()) {
            throw new Exception('Failed to update sit-in record.');
        }

        $user_id = (int) $record['user_id'];
        $updateUser = $conn->prepare("UPDATE users SET sessions_remaining = GREATEST(sessions_remaining - 1, 0) WHERE id = ?");
        $updateUser->bind_param("i", $user_id);
        if (!$updateUser->execute()) {
            throw new Exception('Failed to update remaining sessions.');
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Student session logged out successfully.']);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to logout session.']);
        exit();
    }
}

echo json_encode(['success' => false, 'message' => 'Unsupported request.']);
?>
