<?php
require_once __DIR__ . '/../config/env.php';
loadEnv();

function get_db_connection(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $host = env_get('DB_HOST', 'localhost');
    $username = env_get('DB_USERNAME', 'root');
    $password = env_get('DB_PASSWORD', '');
    $dbname = env_get('DB_NAME', 'login');
    $dsn = 'mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    try {
        $pdo = new PDO($dsn, $username, $password, $options);
    } catch (Throwable $e) {
        error_log('PDO connection failed: ' . $e->getMessage());
        throw new RuntimeException('Database connection failed.');
    }
    return $pdo;
}

$host = env_get('DB_HOST', 'localhost');
$username = env_get('DB_USERNAME', 'root');
$password = env_get('DB_PASSWORD', '');
$dbname = env_get('DB_NAME', 'login');

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Database connection failed. Please try again later.");
}

$conn->set_charset("utf8mb4");
