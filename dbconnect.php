<?php
require_once __DIR__ . '/error_handler.php'; // Include the error handler at the very top

// dbconnect.php - 簡易資料庫連線設定

// Attempt to get credentials from environment variables
$env_host = getenv('DB_HOST');
$env_db   = getenv('DB_NAME');
$env_user = getenv('DB_USER');
$env_pass = getenv('DB_PASS');

// Fallback to hardcoded credentials if environment variables are not set
// WARNING: Hardcoded credentials below are for development/debugging ONLY.
// DO NOT use them in a production environment. Set environment variables instead.
$host = $env_host ?: 'localhost';
$db   = $env_db   ?: 'mat';
$user = $env_user ?: 'mat';
$pass = $env_pass ?: 'daemonbsd@39172291';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}
?>
