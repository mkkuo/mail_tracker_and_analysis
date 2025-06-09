<?php
require_once 'auth.php';
require_once 'dbconnect.php';
// Note: This script performs a state-changing operation (delete) via GET.
// It should be converted to a POST request with CSRF protection.
// require_once 'csrf_guard.php'; // verify_csrf_or_die(); (if it were POST)

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$queue_id = $_GET['id'] ?? null;

if (!$queue_id) {
    die("❌ 未提供 queue ID");
}

// 查詢該筆排程資料
$stmt = $pdo->prepare("
    SELECT q.*, p.user_id AS project_owner
    FROM mail_queue q
    JOIN projects p ON q.project_id = p.id
    WHERE q.id = ?
");
$stmt->execute([$queue_id]);
$queue = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$queue) {
    die("❌ 找不到該排程");
}

if ($queue['status'] !== 'pending') {
    die("❌ 僅能刪除尚未寄出的排程");
}

// 權限判斷
if ($user_role !== 'admin' && $queue['project_owner'] != $user_id) {
    die("❌ 無權刪除此排程");
}

// 執行刪除
$stmt = $pdo->prepare("DELETE FROM mail_queue WHERE id = ?");
$stmt->execute([$queue_id]);

header("Location: mail_queue.php");
exit;
?>

