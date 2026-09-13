<?php
// config/db.php - Database connection with PDO (MySQL / SQLite fallback)

function get_db_connection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $driver = getenv('DB_DRIVER') ?: 'sqlite'; // 'mysql' or 'sqlite'
    $db_file = __DIR__ . '/../database/fisherman.sqlite';

    try {
        if ($driver === 'mysql') {
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $port = getenv('DB_PORT') ?: '3306';
            $dbname = getenv('DB_NAME') ?: 'project_fisherman';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: '';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } else {
            // Default SQLite fallback
            $db_dir = dirname($db_file);
            if (!is_dir($db_dir)) {
                mkdir($db_dir, 0777, true);
            }
            $pdo = new PDO("sqlite:" . $db_file, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            // Enable foreign keys in SQLite
            $pdo->exec("PRAGMA foreign_keys = ON;");
        }
    } catch (PDOException $e) {
        // Fallback to SQLite if MySQL connection fails
        if ($driver === 'mysql') {
            try {
                $pdo = new PDO("sqlite:" . $db_file, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                $pdo->exec("PRAGMA foreign_keys = ON;");
            } catch (PDOException $e2) {
                die("Database Connection Error: " . $e2->getMessage());
            }
        } else {
            die("Database Connection Error: " . $e->getMessage());
        }
    }

    return $pdo;
}
