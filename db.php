<?php
/**
 * Database Connection File
 *
 * This script handles the connection to the database.
 * By default, it attempts to connect to a MySQL database (standard for phpMyAdmin).
 * If the MySQL connection fails (e.g., in a local sandbox without MySQL),
 * it falls back to a local SQLite database for demonstration purposes.
 */

// MySQL Configuration (Edit these to match your phpMyAdmin setup)
$host = 'localhost';
$dbname = 'student_system';
$username = 'root';
$password = '';

try {
    // Attempt MySQL Connection
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // MySQL failed, try SQLite (Fallback for Sandbox/Demo)
    try {
        $sqlite_file = __DIR__ . '/database.sqlite';
        $dsn = "sqlite:$sqlite_file";

        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Enable Foreign Keys for SQLite
        $pdo->exec("PRAGMA foreign_keys = ON;");

        // Check if we need to initialize the SQLite database
        $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='students'");
        if (!$result->fetch()) {
            // Database is empty, load schema
            $sql = file_get_contents(__DIR__ . '/database.sql');

            // Remove MySQL specific syntax that SQLite doesn't like

            // 1. Remove CREATE DATABASE and USE
            $sql = preg_replace('/CREATE DATABASE[^;]+;/i', '', $sql);
            $sql = preg_replace('/USE[^;]+;/i', '', $sql);

            // 2. Fix Primary Keys
            // MySQL: INT AUTO_INCREMENT PRIMARY KEY
            // SQLite: INTEGER PRIMARY KEY AUTOINCREMENT
            $sql = preg_replace('/INT AUTO_INCREMENT PRIMARY KEY/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);

            // 3. Fix other AUTO_INCREMENT if any (though usually caught above)
            $sql = preg_replace('/AUTO_INCREMENT/i', '', $sql); // Just remove it if it wasn't the PK

            // 4. Fix ENUMs
            $sql = preg_replace('/ENUM\([^\)]+\)/i', 'TEXT', $sql);

            // 5. Fix TIMESTAMP
            $sql = preg_replace('/TIMESTAMP/i', 'DATETIME', $sql);

            // 6. Fix CURRENT_TIMESTAMP
            // SQLite uses CURRENT_TIMESTAMP but sometimes syntax varies. Usually fine.

            // Execute the modified SQL commands
            // Split by semicolon to execute individually
            $commands = explode(';', $sql);
            foreach ($commands as $command) {
                if (trim($command)) {
                    try {
                        $pdo->exec($command);
                    } catch (Exception $ex) {
                        // Ignore errors on DROP TABLE or similar if not exists, or empty lines
                        // echo "SQL Error (Ignored): " . $ex->getMessage() . "\n";
                    }
                }
            }
        }
    } catch (PDOException $e2) {
        die("Database connection failed: " . $e2->getMessage());
    }
}
?>
