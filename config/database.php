<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kraepelin_test');

// Create connection
function getConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Initialize database and tables
function initializeDatabase() {
    try {
        // Create database if not exists
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE " . DB_NAME);
        
        // Create tables
        $createTables = "
        CREATE TABLE IF NOT EXISTS test_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            participant_name VARCHAR(255) NOT NULL,
            participant_nip VARCHAR(18) NOT NULL,
            start_time TIMESTAMP NOT NULL,
            end_time TIMESTAMP NULL,
            duration_seconds INT NULL,
            total_answers INT DEFAULT 0,
            filled_answers INT DEFAULT 0,
            completion_percentage DECIMAL(5,2) DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS test_questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id INT NOT NULL,
            row_index INT NOT NULL,
            col_index INT NOT NULL,
            question_number INT NOT NULL,
            FOREIGN KEY (session_id) REFERENCES test_sessions(id) ON DELETE CASCADE,
            INDEX idx_session_position (session_id, row_index, col_index)
        );

        CREATE TABLE IF NOT EXISTS test_answers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id INT NOT NULL,
            row_index INT NOT NULL,
            col_index INT NOT NULL,
            answer_value VARCHAR(2) DEFAULT '',
            is_correct BOOLEAN DEFAULT FALSE,
            answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (session_id) REFERENCES test_sessions(id) ON DELETE CASCADE,
            INDEX idx_session_position (session_id, row_index, col_index)
        );
        ";
        
        $pdo->exec($createTables);
        return true;
    } catch (PDOException $e) {
        error_log("Database initialization failed: " . $e->getMessage());
        return false;
    }
}

// Initialize database on first load
initializeDatabase();
?>