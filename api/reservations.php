<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

// Parse JSON input once
$jsonBody = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $jsonBody = json_decode(file_get_contents('php://input'), true);
}

// Get action from GET, POST, or JSON body
$jsonAction = is_array($jsonBody) ? ($jsonBody['action'] ?? null) : null;
$action = $_GET['action'] ?? $_POST['action'] ?? $jsonAction ?? 'list';

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

$createSitInHistorySql = "CREATE TABLE IF NOT EXISTS sit_in_history (
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
$conn->query($createSitInHistorySql);
$hasReservationIdColumn = $conn->query("SHOW COLUMNS FROM sit_in_history LIKE 'reservation_id'");
if ($hasReservationIdColumn && $hasReservationIdColumn->num_rows === 0) {
    $conn->query("ALTER TABLE sit_in_history ADD COLUMN reservation_id INT NULL AFTER user_id");
}

// Backward compatibility for older reservation tables.
$hasTimeInColumn = $conn->query("SHOW COLUMNS FROM reservations LIKE 'time_in'");
if ($hasTimeInColumn && $hasTimeInColumn->num_rows === 0) {
    $conn->query("ALTER TABLE reservations ADD COLUMN time_in TIME NOT NULL AFTER reservation_date");
    $conn->query("UPDATE reservations SET time_in = reservation_time WHERE time_in = '00:00:00' OR time_in IS NULL");
}

// Create table for PC maintenance status
$createMaintenanceTableSql = "CREATE TABLE IF NOT EXISTS pc_maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    laboratory VARCHAR(20) NOT NULL,
    pc_number INT NOT NULL,
    is_under_maintenance BOOLEAN DEFAULT FALSE,
    reason VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_lab_pc (laboratory, pc_number)
)";
$conn->query($createMaintenanceTableSql);

$allowedLabs = ['524', '526', '530', '540', '544'];

// Backward compatibility for older maintenance table schema.
$hasMaintenanceLabColumn = $conn->query("SHOW COLUMNS FROM pc_maintenance LIKE 'laboratory'");
if ($hasMaintenanceLabColumn && $hasMaintenanceLabColumn->num_rows === 0) {
    $conn->query("ALTER TABLE pc_maintenance ADD COLUMN laboratory VARCHAR(20) NOT NULL DEFAULT '524' AFTER id");
}

$maintenanceIndexResult = $conn->query("SHOW INDEX FROM pc_maintenance WHERE Column_name = 'pc_number' AND Non_unique = 0");
if ($maintenanceIndexResult) {
    while ($idx = $maintenanceIndexResult->fetch_assoc()) {
        $keyName = $idx['Key_name'] ?? '';
        if ($keyName && $keyName !== 'PRIMARY' && $keyName !== 'uniq_lab_pc') {
            $safeKey = str_replace('`', '', $keyName);
            $conn->query("ALTER TABLE pc_maintenance DROP INDEX `$safeKey`");
        }
    }
}
$hasCompositeMaintenanceIndex = $conn->query("SHOW INDEX FROM pc_maintenance WHERE Key_name = 'uniq_lab_pc'");
if ($hasCompositeMaintenanceIndex && $hasCompositeMaintenanceIndex->num_rows === 0) {
    $conn->query("ALTER TABLE pc_maintenance ADD UNIQUE KEY uniq_lab_pc (laboratory, pc_number)");
}

// Initialize all PCs for each lab if they don't exist
$insertMaintenanceStmt = $conn->prepare("
    INSERT INTO pc_maintenance (laboratory, pc_number, is_under_maintenance)
    VALUES (?, ?, FALSE)
    ON DUPLICATE KEY UPDATE pc_number = VALUES(pc_number)
");
foreach ($allowedLabs as $lab) {
    for ($i = 1; $i <= 50; $i++) {
        $insertMaintenanceStmt->bind_param("si", $lab, $i);
        $insertMaintenanceStmt->execute();
    }
}


function respond($payload) {
    echo json_encode($payload);
    exit();
}

function is_admin() {
    return isset($_SESSION['admin_id']);
}

function is_student() {
    return isset($_SESSION['user_id']);
}

function validate_reservation_payload($laboratory, $reservationDate, $timeIn, $timeOut, $pcNumber, $purpose) {
    global $allowedLabs;

    if (!in_array($laboratory, $allowedLabs, true)) {
        respond(['success' => false, 'message' => 'Please select a valid laboratory.']);
    }
    if ($reservationDate === '' || $timeIn === '' || $timeOut === '' || $pcNumber <= 0 || $purpose === '') {
        respond(['success' => false, 'message' => 'All reservation fields are required.']);
    }
    if (strtotime($reservationDate) === false) {
        respond(['success' => false, 'message' => 'Invalid reservation date.']);
    }
    if ($reservationDate < date('Y-m-d')) {
        respond(['success' => false, 'message' => 'Reservation date cannot be in the past.']);
    }
    if (strlen($purpose) > 255) {
        respond(['success' => false, 'message' => 'Purpose is too long.']);
    }
    if ($pcNumber < 1 || $pcNumber > 50) {
        respond(['success' => false, 'message' => 'Please select a valid PC number from 1 to 50.']);
    }

    $timeInTimestamp = strtotime($reservationDate . ' ' . $timeIn);
    $timeOutTimestamp = strtotime($reservationDate . ' ' . $timeOut);
    if ($timeInTimestamp === false || $timeOutTimestamp === false) {
        respond(['success' => false, 'message' => 'Invalid reservation time range.']);
    }
    if ($timeOutTimestamp <= $timeInTimestamp) {
        respond(['success' => false, 'message' => 'Time-out must be later than Time-in.']);
    }

    $durationMinutes = (int) round(($timeOutTimestamp - $timeInTimestamp) / 60);
    if ($durationMinutes < 30 || $durationMinutes > 120) {
        respond(['success' => false, 'message' => 'Reservation duration must be between 30 minutes and 2 hours only.']);
    }
}

if ($action === 'create') {
    if (!is_student()) {
        respond(['success' => false, 'message' => 'Unauthorized']);
    }

    $userId = (int) $_SESSION['user_id'];
    $laboratory = trim($_POST['laboratory'] ?? '');
    $reservationDate = trim($_POST['reservation_date'] ?? '');
    $timeIn = trim($_POST['time_in'] ?? '');
    $timeOut = trim($_POST['time_out'] ?? '');
    $pcNumber = (int) ($_POST['pc_number'] ?? 0);
    $purpose = trim($_POST['purpose'] ?? '');

    validate_reservation_payload($laboratory, $reservationDate, $timeIn, $timeOut, $pcNumber, $purpose);

    $stmt = $conn->prepare("
        INSERT INTO reservations (user_id, laboratory, reservation_date, time_in, time_out, pc_number, purpose, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->bind_param("issssis", $userId, $laboratory, $reservationDate, $timeIn, $timeOut, $pcNumber, $purpose);

    if (!$stmt->execute()) {
        respond(['success' => false, 'message' => 'Failed to submit reservation.']);
    }

    respond(['success' => true, 'message' => 'Reservation submitted successfully.']);
}

if ($action === 'admin_create') {
    if (!is_admin()) {
        respond(['success' => false, 'message' => 'Unauthorized']);
    }

    $studentId = trim($_POST['student_id'] ?? '');
    $laboratory = trim($_POST['laboratory'] ?? '');
    $reservationDate = trim($_POST['reservation_date'] ?? '');
    $timeIn = trim($_POST['time_in'] ?? '');
    $timeOut = trim($_POST['time_out'] ?? '');
    $pcNumber = (int) ($_POST['pc_number'] ?? 0);
    $purpose = trim($_POST['purpose'] ?? '');

    if ($studentId === '') {
        respond(['success' => false, 'message' => 'Student ID is required.']);
    }

    validate_reservation_payload($laboratory, $reservationDate, $timeIn, $timeOut, $pcNumber, $purpose);

    $lookup = $conn->prepare("SELECT id FROM users WHERE student_id = ? LIMIT 1");
    $lookup->bind_param("s", $studentId);
    $lookup->execute();
    $user = $lookup->get_result()->fetch_assoc();

    if (!$user) {
        respond(['success' => false, 'message' => 'Student ID was not found.']);
    }

    $userId = (int) $user['id'];
    $stmt = $conn->prepare("
        INSERT INTO reservations (user_id, laboratory, reservation_date, time_in, time_out, pc_number, purpose, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->bind_param("issssis", $userId, $laboratory, $reservationDate, $timeIn, $timeOut, $pcNumber, $purpose);

    if (!$stmt->execute()) {
        respond(['success' => false, 'message' => 'Failed to create admin reservation.']);
    }

    respond(['success' => true, 'message' => 'Reservation created successfully for the student.']);
}

if ($action === 'update_status') {
    if (!is_admin()) {
        respond(['success' => false, 'message' => 'Unauthorized']);
    }

    $reservationId = (int) ($_POST['reservation_id'] ?? 0);
    $status = strtolower(trim($_POST['status'] ?? ''));
    $allowedStatuses = ['approved', 'denied'];

    if ($reservationId <= 0 || !in_array($status, $allowedStatuses, true)) {
        respond(['success' => false, 'message' => 'Invalid reservation update request.']);
    }

    $reservationLookup = $conn->prepare("
        SELECT id, user_id, laboratory, purpose, reservation_date
        FROM reservations
        WHERE id = ?
        LIMIT 1
    ");
    $reservationLookup->bind_param("i", $reservationId);
    $reservationLookup->execute();
    $reservation = $reservationLookup->get_result()->fetch_assoc();

    if (!$reservation) {
        respond(['success' => false, 'message' => 'Reservation not found.']);
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $reservationId);
        if (!$stmt->execute()) {
            throw new Exception('Failed to update reservation.');
        }

        // If the student already has an active sit-in session for today, sync it with the approved reservation.
        if ($status === 'approved') {
            $userId = (int) $reservation['user_id'];
            $reservationDate = (string) $reservation['reservation_date'];
            $lab = (string) $reservation['laboratory'];
            $purpose = (string) $reservation['purpose'];

            $syncStmt = $conn->prepare("
                UPDATE sit_in_history
                SET reservation_id = ?, laboratory = ?, purpose = ?
                WHERE user_id = ?
                  AND DATE(time_in) = ?
                  AND (LOWER(status) = 'active' OR LOWER(status) = 'ongoing')
                  AND time_out IS NULL
                ORDER BY time_in DESC
                LIMIT 1
            ");
            $syncStmt->bind_param("issis", $reservationId, $lab, $purpose, $userId, $reservationDate);
            if (!$syncStmt->execute()) {
                throw new Exception('Failed to sync active sit-in session.');
            }
        }

        $conn->commit();
        respond(['success' => true, 'message' => 'Reservation status updated successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Failed to update reservation.']);
    }
}

if ($action === 'my') {
    if (!is_student()) {
        respond(['success' => false, 'message' => 'Unauthorized']);
    }

    $userId = (int) $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT
            u.student_id,
            laboratory,
            pc_number,
            DATE_FORMAT(reservation_date, '%b %d, %Y') AS reservation_date,
            DATE_FORMAT(time_in, '%h:%i %p') AS time_in,
            DATE_FORMAT(time_out, '%h:%i %p') AS time_out,
            purpose,
            status
        FROM reservations
        INNER JOIN users u ON u.id = reservations.user_id
        WHERE user_id = ?
        ORDER BY reservation_date DESC, time_in DESC, id DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    respond(['success' => true, 'data' => $rows]);
}

if ($action === 'list') {
    if (!is_admin()) {
        respond(['success' => false, 'message' => 'Unauthorized']);
    }

    $search = trim($_GET['search'] ?? '');
    $like = '%' . $search . '%';

    $stmt = $conn->prepare("
        SELECT
            r.id,
            u.student_id,
            CONCAT(u.first_name, ' ', COALESCE(NULLIF(u.middle_name, ''), ''), IF(u.middle_name IS NULL OR u.middle_name = '', '', ' '), u.last_name) AS name,
            r.laboratory,
            r.pc_number,
            DATE_FORMAT(r.reservation_date, '%b %d, %Y') AS reservation_date,
            DATE_FORMAT(r.time_in, '%h:%i %p') AS time_in,
            DATE_FORMAT(r.time_out, '%h:%i %p') AS time_out,
            r.purpose,
            r.status
        FROM reservations r
        INNER JOIN users u ON u.id = r.user_id
        WHERE (
            u.student_id LIKE ?
            OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?
            OR r.laboratory LIKE ?
            OR CAST(r.pc_number AS CHAR) LIKE ?
            OR r.purpose LIKE ?
            OR r.status LIKE ?
        )
        ORDER BY
            CASE LOWER(r.status)
                WHEN 'pending' THEN 1
                WHEN 'approved' THEN 2
                WHEN 'denied' THEN 3
                ELSE 4
            END,
            r.reservation_date DESC,
            r.time_in DESC,
            r.id DESC
    ");
    $stmt->bind_param("ssssss", $like, $like, $like, $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    respond(['success' => true, 'data' => $rows]);
}

if ($action === 'availability') {
    if (!is_admin() && !is_student()) {
        respond(['success' => false, 'message' => 'Unauthorized']);
    }

    $laboratory = trim($_GET['laboratory'] ?? '');
    $reservationDate = trim($_GET['reservation_date'] ?? '');
    $timeIn = trim($_GET['time_in'] ?? '');
    $timeOut = trim($_GET['time_out'] ?? '');

    if ($laboratory === '' || !in_array($laboratory, $allowedLabs, true)) {
        respond(['success' => false, 'message' => 'Please select a valid laboratory.']);
    }

    $occupiedByLab = array_fill_keys($allowedLabs, []);
    $occupiedPcNumbers = [];

    $activeStmt = $conn->prepare("\n        SELECT COALESCE(r.laboratory, s.laboratory) AS laboratory, COALESCE(r.pc_number, 0) AS pc_number\n        FROM sit_in_history s\n        LEFT JOIN reservations r ON r.id = s.reservation_id\n        WHERE (LOWER(s.status) = 'active' OR LOWER(s.status) = 'ongoing')\n          AND s.time_out IS NULL\n    ");
    $activeStmt->execute();
    $activeResult = $activeStmt->get_result();
    while ($row = $activeResult->fetch_assoc()) {
        $lab = trim((string) $row['laboratory']);
        $pc = (int) $row['pc_number'];
        if ($pc > 0 && in_array($lab, $allowedLabs, true)) {
            if (!in_array($pc, $occupiedByLab[$lab], true)) {
                $occupiedByLab[$lab][] = $pc;
            }
            if ($lab === $laboratory && !in_array($pc, $occupiedPcNumbers, true)) {
                $occupiedPcNumbers[] = $pc;
            }
        }
    }

    if ($reservationDate !== '' && $timeIn !== '' && $timeOut !== '') {
        $timeInTimestamp = strtotime($timeIn);
        $timeOutTimestamp = strtotime($timeOut);
        if ($timeInTimestamp !== false && $timeOutTimestamp !== false && $timeOutTimestamp > $timeInTimestamp) {
            $timeInValue = date('H:i:s', $timeInTimestamp);
            $timeOutValue = date('H:i:s', $timeOutTimestamp);
            $approvedStmt = $conn->prepare("\n                SELECT laboratory, pc_number\n                FROM reservations\n                WHERE status = 'approved'\n                  AND reservation_date = ?\n                  AND NOT (time_out <= ? OR time_in >= ?)\n            ");
            $approvedStmt->bind_param("sss", $reservationDate, $timeInValue, $timeOutValue);
            $approvedStmt->execute();
            $approvedResult = $approvedStmt->get_result();
            while ($row = $approvedResult->fetch_assoc()) {
                $lab = trim((string) $row['laboratory']);
                $pc = (int) $row['pc_number'];
                if ($pc > 0 && in_array($lab, $allowedLabs, true)) {
                    if (!in_array($pc, $occupiedByLab[$lab], true)) {
                        $occupiedByLab[$lab][] = $pc;
                    }
                    if ($lab === $laboratory && !in_array($pc, $occupiedPcNumbers, true)) {
                        $occupiedPcNumbers[] = $pc;
                    }
                }
            }
        }
    }

    sort($occupiedPcNumbers);
    foreach ($occupiedByLab as $lab => &$pcs) {
        sort($pcs);
    }
    unset($pcs);

    // Get maintenance PCs for the selected lab
    $maintenanceStmt = $conn->prepare("SELECT pc_number FROM pc_maintenance WHERE is_under_maintenance = TRUE AND laboratory = ?");
    $maintenanceStmt->bind_param("s", $laboratory);
    $maintenanceStmt->execute();
    $maintenanceResult = $maintenanceStmt->get_result();
    $maintenancePcNumbers = [];
    while ($row = $maintenanceResult->fetch_assoc()) {
        $maintenancePcNumbers[] = (string) $row['pc_number'];
    }

    respond([
        'success' => true,
        'occupied_pcs' => array_map('strval', $occupiedPcNumbers),
        'maintenance_pcs' => $maintenancePcNumbers,
        'occupied_by_lab' => array_map(function ($pcs) {
            return array_map('strval', $pcs);
        }, $occupiedByLab),
    ]);
}

if ($action === 'admin_logs') {
    if (!is_admin()) {
        respond(['success' => false, 'message' => 'Unauthorized']);
    }

    $search = trim($_GET['search'] ?? '');
    $laboratory = trim($_GET['laboratory'] ?? '');
    $pcNumber = (int) ($_GET['pc_number'] ?? 0);

    if ($laboratory !== '' && !in_array($laboratory, $allowedLabs, true)) {
        respond(['success' => false, 'message' => 'Invalid laboratory filter.']);
    }

    if ($pcNumber < 0 || $pcNumber > 50) {
        respond(['success' => false, 'message' => 'Invalid PC number filter.']);
    }

    $like = '%' . $search . '%';
    $labFilter = $laboratory === '' ? '' : $laboratory;
    $pcFilter = $pcNumber > 0 ? $pcNumber : 0;

    $stmt = $conn->prepare("
        SELECT
            u.student_id,
            CONCAT(u.first_name, ' ', COALESCE(NULLIF(u.middle_name, ''), ''), IF(u.middle_name IS NULL OR u.middle_name = '', '', ' '), u.last_name) AS name,
            r.laboratory,
            r.pc_number,
            DATE_FORMAT(r.reservation_date, '%b %d, %Y') AS reservation_date,
            DATE_FORMAT(r.time_in, '%h:%i %p') AS time_in,
            DATE_FORMAT(r.time_out, '%h:%i %p') AS time_out,
            r.purpose,
            r.status
        FROM reservations r
        INNER JOIN users u ON u.id = r.user_id
        WHERE (? = '' OR r.laboratory = ?)
          AND (? = 0 OR r.pc_number = ?)
          AND (
              u.student_id LIKE ?
              OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?
              OR r.laboratory LIKE ?
              OR CAST(r.pc_number AS CHAR) LIKE ?
              OR r.purpose LIKE ?
              OR r.status LIKE ?
              OR DATE_FORMAT(r.reservation_date, '%b %d, %Y') LIKE ?
          )
        ORDER BY r.reservation_date DESC, r.time_in DESC, r.id DESC
    ");
    $stmt->bind_param(
        "ssiisssssss",
        $labFilter,
        $labFilter,
        $pcFilter,
        $pcFilter,
        $like,
        $like,
        $like,
        $like,
        $like,
        $like,
        $like
    );
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    respond(['success' => true, 'data' => $rows]);
}

if ($action === 'get_maintenance_status') {
    if (!is_admin()) {
        respond(['success' => false, 'message' => 'Unauthorized']);
    }

    $laboratory = trim($_GET['laboratory'] ?? '');
    if (!in_array($laboratory, $allowedLabs, true)) {
        respond(['success' => false, 'message' => 'Please select a valid laboratory.']);
    }

    $stmt = $conn->prepare("SELECT pc_number FROM pc_maintenance WHERE is_under_maintenance = TRUE AND laboratory = ?");
    $stmt->bind_param("s", $laboratory);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $maintenancePcs = [];
    while ($row = $result->fetch_assoc()) {
        $maintenancePcs[] = (string) $row['pc_number'];
    }

    respond(['success' => true, 'maintenance_pcs' => $maintenancePcs]);
}

if ($action === 'add_maintenance') {
    if (!is_admin()) {
        respond(['success' => false, 'message' => 'Unauthorized']);
    }

    $laboratory = trim((string) ($jsonBody['laboratory'] ?? ''));
    $pcNumber = (int) ($jsonBody['pc_number'] ?? 0);

    if (!in_array($laboratory, $allowedLabs, true)) {
        respond(['success' => false, 'message' => 'Please select a valid laboratory.']);
    }
    if ($pcNumber < 1 || $pcNumber > 50) {
        respond(['success' => false, 'message' => 'Invalid PC number.']);
    }

    $stmt = $conn->prepare("UPDATE pc_maintenance SET is_under_maintenance = TRUE WHERE laboratory = ? AND pc_number = ?");
    $stmt->bind_param("si", $laboratory, $pcNumber);
    
    if ($stmt->execute()) {
        respond(['success' => true, 'message' => "PC $pcNumber marked as under maintenance."]);
    } else {
        respond(['success' => false, 'message' => 'Failed to update PC maintenance status.']);
    }
}

if ($action === 'remove_maintenance') {
    if (!is_admin()) {
        respond(['success' => false, 'message' => 'Unauthorized']);
    }

    $laboratory = trim((string) ($jsonBody['laboratory'] ?? ''));
    $pcNumber = (int) ($jsonBody['pc_number'] ?? 0);

    if (!in_array($laboratory, $allowedLabs, true)) {
        respond(['success' => false, 'message' => 'Please select a valid laboratory.']);
    }
    if ($pcNumber < 1 || $pcNumber > 50) {
        respond(['success' => false, 'message' => 'Invalid PC number.']);
    }

    $stmt = $conn->prepare("UPDATE pc_maintenance SET is_under_maintenance = FALSE WHERE laboratory = ? AND pc_number = ?");
    $stmt->bind_param("si", $laboratory, $pcNumber);
    
    if ($stmt->execute()) {
        respond(['success' => true, 'message' => "PC $pcNumber is now available."]);
    } else {
        respond(['success' => false, 'message' => 'Failed to update PC maintenance status.']);
    }
}

respond(['success' => false, 'message' => 'Invalid action.']);
