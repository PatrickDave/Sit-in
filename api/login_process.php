<?php
session_start();
require_once 'db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_id = trim($_POST['loginId'] ?? $_POST['studentId'] ?? '');
    $password = $_POST['password'] ?? '';
    $requested_lab = trim($_POST['sitLab'] ?? '');
    $requested_purpose = trim($_POST['purpose'] ?? '');

    if ($login_id === '' || $password === '') {
        header("Location: ../html/login.html?error=missing_fields");
        exit();
    }

    // Try admin login first (username)
    $admin_stmt = $conn->prepare("SELECT id, password FROM admins WHERE username = ?");
    $admin_stmt->bind_param("s", $login_id);
    $admin_stmt->execute();
    $admin_result = $admin_stmt->get_result();

    if ($admin_user = $admin_result->fetch_assoc()) {
        if (password_verify($password, $admin_user['password'])) {
            $_SESSION['admin_id'] = $admin_user['id'];
            $_SESSION['admin_user'] = $login_id;
            header("Location: ../html/admin/adminDashboard.html");
            exit();
        }

        header("Location: ../html/login.html?error=invalid_password&userType=admin");
        exit();
    }

    // If no matching admin username, try student ID.
    $student_stmt = $conn->prepare("SELECT id, password, sessions_remaining, course FROM users WHERE student_id = ?");
    $student_stmt->bind_param("s", $login_id);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();

    if ($student_user = $student_result->fetch_assoc()) {
        if (password_verify($password, $student_user['password'])) {
            $student_user_id = (int) $student_user['id'];
            $_SESSION['user_id'] = $student_user_id;

            // Auto-create active sit-in record on student login so admin records
            // reflect real student logins without manual SQL inserts.
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
            $conn->query($createTableSql);

            // Prevent duplicate active sessions for the same student.
            $active_check = $conn->prepare(
                "SELECT id FROM sit_in_history WHERE user_id = ? AND (LOWER(status) = 'active' OR LOWER(status) = 'ongoing') AND time_out IS NULL LIMIT 1"
            );
            $active_check->bind_param("i", $student_user_id);
            $active_check->execute();
            $already_active = $active_check->get_result()->num_rows > 0;

            // Only auto-start when the student still has remaining sessions.
            if (!$already_active && (int) $student_user['sessions_remaining'] > 0) {
                $allowed_labs = ['524', '526', '530', '540', '544'];

                // Use requested values when available; otherwise generate dynamic defaults.
                if (!in_array($requested_lab, $allowed_labs, true)) {
                    // Dynamic lab assignment (changes over time and per user).
                    $lab_index = abs(crc32($login_id . date('YmdHi'))) % count($allowed_labs);
                    $requested_lab = $allowed_labs[$lab_index];
                }

                if ($requested_purpose === '') {
                    $course = strtoupper(trim($student_user['course'] ?? ''));
                    if (strpos($course, 'IT') !== false) {
                        $requested_purpose = 'Programming Practice';
                    } elseif (strpos($course, 'CS') !== false) {
                        $requested_purpose = 'Research and Development';
                    } else {
                        $requested_purpose = 'Academic Sit-in';
                    }
                }

                $start_session = $conn->prepare(
                    "INSERT INTO sit_in_history (user_id, laboratory, purpose, time_in, status)
                     VALUES (?, ?, ?, NOW(), 'active')"
                );
                $start_session->bind_param("iss", $student_user_id, $requested_lab, $requested_purpose);
                $start_session->execute();
            }

            header("Location: ../html/student/studentDashboard.html");
            exit();
        }

        header("Location: ../html/login.html?error=invalid_password&userType=student");
        exit();
    }

    header("Location: ../html/login.html?error=not_found");
    exit();
}
?>
