<?php
require_once 'db.php';

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

if ($conn->query($createTableSql)) {
    echo "sit_in_history table is ready.";
} else {
    echo "Error creating table: " . $conn->error;
}
?>
