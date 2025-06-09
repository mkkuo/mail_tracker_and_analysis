<?php
// dbconnect.php - 簡易資料庫連線設定
// 可透過環境變數 DB_HOST、DB_NAME、DB_USER、DB_PASS 覆蓋預設值
$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'mat';
$user = getenv('DB_USER') ?: 'mat';
$pass = getenv('DB_PASS') ?: 'uncle@panel';
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