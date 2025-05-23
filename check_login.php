<?php
require 'dbconnect.php';
session_start();

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
  $_SESSION['user_id'] = $user['id'];
  $_SESSION['user_email'] = $user['email'];
  $_SESSION['user_role'] = $user['atype'];

  // 新增登入紀錄
  $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
  $stmt_log = $pdo->prepare("INSERT INTO login_log (user_id, email, ip) VALUES (?, ?, ?)");
  $stmt_log->execute([$user['id'], $user['email'], $ip]);

  header("Location: dashboard.php");
  exit;
} else {
  header("Location: login.php?error=登入失敗，請檢查帳密");
  exit;
}

