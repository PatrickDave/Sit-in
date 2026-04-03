<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

function full_name_from_row($row) {
    $first = trim($row['first_name'] ?? '');
    $middle = trim($row['middle_name'] ?? '');
    $last = trim($row['last_name'] ?? '');
    return trim($first . ' ' . $middle . ' ' . $last);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if ($method === 'GET' && $action === 'list') {
    $search = trim($_GET['search'] ?? '');

    if ($search !== '') {
        $like = '%' . $search . '%';
        $stmt = $conn->prepare(
            "SELECT id, student_id, first_name, middle_name, last_name, email, course, address, year_level, sessions_remaining
             FROM users
             WHERE student_id LIKE ? OR first_name LIKE ? OR middle_name LIKE ? OR last_name LIKE ?
             ORDER BY id DESC"
        );
        $stmt->bind_param("ssss", $like, $like, $like, $like);
    } else {
        $stmt = $conn->prepare(
            "SELECT id, student_id, first_name, middle_name, last_name, email, course, address, year_level, sessions_remaining
             FROM users
             ORDER BY id DESC"
        );
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = [
            'id' => (int) $row['id'],
            'student_id' => $row['student_id'],
            'name' => full_name_from_row($row),
            'first_name' => $row['first_name'],
            'middle_name' => $row['middle_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'] ?? '',
            'course' => $row['course'] ?? '',
            'address' => $row['address'] ?? '',
            'year_level' => (int) $row['year_level'],
            'sessions_remaining' => (int) $row['sessions_remaining'],
        ];
    }

    echo json_encode(['success' => true, 'data' => $students]);
    exit();
}

if ($method === 'POST' && $action === 'create') {
    $student_id = trim($_POST['student_id'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $year_level = (int) ($_POST['year_level'] ?? 0);
    $course = trim($_POST['course'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $sessions_remaining = (int) ($_POST['sessions_remaining'] ?? 30);

    if ($student_id === '' || $first_name === '' || $last_name === '' || $email === '' || $year_level < 1 || $course === '' || $address === '' || $password === '') {
        echo json_encode(['success' => false, 'message' => 'Please complete all required fields.']);
        exit();
    }

    $check = $conn->prepare("SELECT id FROM users WHERE student_id = ? OR email = ?");
    $check->bind_param("ss", $student_id, $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Student ID or email already exists.']);
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare(
        "INSERT INTO users
            (student_id, last_name, first_name, middle_name, email, year_level, course, address, password, profile_picture, sessions_remaining)
         VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, 'default.png', ?)"
    );
    $stmt->bind_param(
        "sssssisssi",
        $student_id,
        $last_name,
        $first_name,
        $middle_name,
        $email,
        $year_level,
        $course,
        $address,
        $hashed_password,
        $sessions_remaining
    );

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to add student.']);
        exit();
    }

    echo json_encode(['success' => true, 'message' => 'Student added successfully.']);
    exit();
}

if ($method === 'POST' && $action === 'update') {
    $id = (int) ($_POST['id'] ?? 0);
    $student_id = trim($_POST['student_id'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $year_level = (int) ($_POST['year_level'] ?? 0);
    $course = trim($_POST['course'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $sessions_remaining = (int) ($_POST['sessions_remaining'] ?? 0);
    $password = $_POST['password'] ?? '';

    if ($id <= 0 || $student_id === '' || $first_name === '' || $last_name === '' || $email === '' || $year_level < 1 || $course === '' || $address === '') {
        echo json_encode(['success' => false, 'message' => 'Please complete all required fields.']);
        exit();
    }

    $check = $conn->prepare("SELECT id FROM users WHERE (student_id = ? OR email = ?) AND id <> ?");
    $check->bind_param("ssi", $student_id, $email, $id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Student ID or email already used by another student.']);
        exit();
    }

    if ($password !== '') {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            "UPDATE users
             SET student_id = ?, first_name = ?, middle_name = ?, last_name = ?, email = ?, year_level = ?, course = ?, address = ?, sessions_remaining = ?, password = ?
             WHERE id = ?"
        );
        $stmt->bind_param(
            "sssssissisi",
            $student_id,
            $first_name,
            $middle_name,
            $last_name,
            $email,
            $year_level,
            $course,
            $address,
            $sessions_remaining,
            $hashed_password,
            $id
        );
    } else {
        $stmt = $conn->prepare(
            "UPDATE users
             SET student_id = ?, first_name = ?, middle_name = ?, last_name = ?, email = ?, year_level = ?, course = ?, address = ?, sessions_remaining = ?
             WHERE id = ?"
        );
        $stmt->bind_param(
            "sssssissii",
            $student_id,
            $first_name,
            $middle_name,
            $last_name,
            $email,
            $year_level,
            $course,
            $address,
            $sessions_remaining,
            $id
        );
    }

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to update student.']);
        exit();
    }

    echo json_encode(['success' => true, 'message' => 'Student updated successfully.']);
    exit();
}

if ($method === 'POST' && $action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid student ID.']);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete student.']);
        exit();
    }

    echo json_encode(['success' => true, 'message' => 'Student deleted successfully.']);
    exit();
}

if ($method === 'POST' && $action === 'reset_sessions') {
    $default_sessions = (int) ($_POST['default_sessions'] ?? 30);
    if ($default_sessions < 0) {
        $default_sessions = 30;
    }

    $stmt = $conn->prepare("UPDATE users SET sessions_remaining = ?");
    $stmt->bind_param("i", $default_sessions);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to reset sessions.']);
        exit();
    }

    echo json_encode(['success' => true, 'message' => 'All student sessions were reset.']);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Unsupported request.']);
?>
