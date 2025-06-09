<?php
require 'dbconnect.php';

$pid = $_GET['pid'] ?? null;
$uid = $_GET['uid'] ?? null;
$rid = $_GET['rid'] ?? null;
$raw_url = $_GET['url'] ?? null;

if (!$pid || !$uid || !$rid || !$raw_url) {
    die("❌ 無效請求");
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$target_url = urldecode($raw_url);

// 記錄點擊行為
$stmt = $pdo->prepare("
  INSERT INTO mail_click_log (project_id, user_id, recipient_id, ip, user_agent, clicked_url)
  VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->execute([$pid, $uid, $rid, $ip, $ua, $target_url]);

// 跳轉
header("Location: $target_url");
exit;

