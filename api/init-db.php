<?php
// Database Initialization Script
// This creates the required tables for the Sit-in system

include 'db-config.php';

try {
    // Create users table
    $sql_users = "
    CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        student_id VARCHAR(50) UNIQUE NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        year_level INT NOT NULL CHECK (year_level IN (1, 2, 3, 4)),
        course VARCHAR(150) NOT NULL,
        address VARCHAR(255) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        profile_photo_path VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    // Create sessions table
    $sql_sessions = "
    CREATE TABLE IF NOT EXISTS sessions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    // Create indexes
    $sql_indexes = "
    CREATE INDEX IF NOT EXISTS idx_email ON users(email);
    CREATE INDEX IF NOT EXISTS idx_student_id ON users(student_id);
    CREATE INDEX IF NOT EXISTS idx_expires_at ON sessions(expires_at);
    ";

    // Execute table creation
    $pdo->exec($sql_users);
    $pdo->exec($sql_sessions);

    // Create indexes separately (some may fail if they exist, that's OK)
    try { $pdo->exec("CREATE INDEX idx_email ON users(email)"); } catch (Exception $e) {}
    try { $pdo->exec("CREATE INDEX idx_student_id ON users(student_id)"); } catch (Exception $e) {}
    try { $pdo->exec("CREATE INDEX idx_expires_at ON sessions(expires_at)"); } catch (Exception $e) {}

    sendSuccess('Database initialized successfully', [
        'usuarios_table' => 'created',
        'sessions_table' => 'created'
    ]);

} catch (Exception $e) {
    sendError('Database initialization failed: ' . $e->getMessage(), 500);
}

?>
