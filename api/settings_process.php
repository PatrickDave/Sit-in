<?php
session_start();
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Create settings table if not exists
$sql = "CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($sql);

// Insert default settings if not exist
$defaults = [
    'session_timeout' => '4',
    'max_daily_sessions' => '3',
    'lab_open_time' => '07:00',
    'lab_close_time' => '18:00'
];

foreach ($defaults as $key => $value) {
    $stmt = $conn->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
    $stmt->bind_param("ss", $key, $value);
    $stmt->execute();
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Get all settings
    $result = $conn->query("SELECT setting_key, setting_value FROM system_settings");
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    echo json_encode(['success' => true, 'data' => $settings]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $session_timeout = $_POST['session_timeout'] ?? '4';
    $max_daily_sessions = $_POST['max_daily_sessions'] ?? '3';
    $lab_open_time = $_POST['lab_open_time'] ?? '07:00';
    $lab_close_time = $_POST['lab_close_time'] ?? '18:00';
    
    $settings = [
        'session_timeout' => $session_timeout,
        'max_daily_sessions' => $max_daily_sessions,
        'lab_open_time' => $lab_open_time,
        'lab_close_time' => $lab_close_time
    ];
    
    foreach ($settings as $key => $value) {
        $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $value, $key);
        $stmt->execute();
    }
    
    echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
    exit();
}
?>
