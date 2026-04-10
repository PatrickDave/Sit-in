<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

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

$hasTimeOutColumn = $conn->query("SHOW COLUMNS FROM reservations LIKE 'time_out'");
if ($hasTimeOutColumn && $hasTimeOutColumn->num_rows === 0) {
    $conn->query("ALTER TABLE reservations ADD COLUMN time_out TIME NOT NULL AFTER time_in");
    $conn->query("UPDATE reservations SET time_out = ADDTIME(time_in, '02:00:00') WHERE time_out = '00:00:00' OR time_out IS NULL");
}

$hasPcColumn = $conn->query("SHOW COLUMNS FROM reservations LIKE 'pc_number'");
if ($hasPcColumn && $hasPcColumn->num_rows === 0) {
    $conn->query("ALTER TABLE reservations ADD COLUMN pc_number INT NOT NULL DEFAULT 1 AFTER time_out");
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
    $allowedLabs = ['524', '526', '530', '540', '544'];

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

if ($action === 'admin_logs') {
    if (!is_admin()) {
        respond(['success' => false, 'message' => 'Unauthorized']);
    }

    $allowedLabs = ['524', '526', '530', '540', '544'];
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

respond(['success' => false, 'message' => 'Invalid action.']);
?>
