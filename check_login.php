<?php
// dbconnect.php includes error_handler.php, so it should be first.
require_once 'dbconnect.php';
session_start(); // Must be after error_handler.php is loaded if error_handler.php also tries to manage sessions (it does)

// Note: Login form should also have CSRF protection.
// require_once 'csrf_guard.php';
// verify_csrf_or_die();
// This part is commented out as it's outside the scope of "require_once" task,
// but login.php and check_login.php should be updated for CSRF in a separate step.

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

