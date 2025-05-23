<?php
require 'dbconnect.php';

$project_id   = $_GET['pid'] ?? null;
$user_id      = $_GET['uid'] ?? null;
$recipient_id = $_GET['rid'] ?? null;

if ($project_id && $user_id && $recipient_id) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $stmt = $pdo->prepare("
        INSERT INTO mail_open_log (project_id, user_id, recipient_id, ip, user_agent)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$project_id, $user_id, $recipient_id, $ip, $ua]);
}

// 輸出 1x1 透明 GIF
header('Content-Type: image/gif');
echo base64_decode(
    'R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==' // 透明像素
);
exit;

