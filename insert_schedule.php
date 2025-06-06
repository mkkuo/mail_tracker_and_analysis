<?php
require_once 'auth.php';
require_once 'csrf_guard.php'; // Needs to be after auth.php
verify_csrf_or_die(); // Call this early
require_once 'dbconnect.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

$project_id = $_POST['project_id'] ?? null;
$template_id = $_POST['template_id'] ?? null;
$recipients = $_POST['recipients'] ?? [];
$scheduled_at = $_POST['scheduled_at'] ?? null;

// 基本驗證
if (!$project_id || !$template_id || empty($recipients) || !$scheduled_at) {
    die("❌ 所有欄位皆為必填！");
}

// 權限檢查：是否有這個專案
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) {
    die("❌ 專案不存在。");
}
if ($user_role !== 'admin' && $project['user_id'] != $user_id) {
    die("❌ 無權操作此專案。");
}

// 建立多筆 queue
$stmt = $pdo->prepare("
    INSERT INTO mail_queue (project_id, template_id, recipient_id, scheduled_at, status)
    VALUES (?, ?, ?, ?, 'pending')
");

foreach ($recipients as $rid) {
    $stmt->execute([$project_id, $template_id, $rid, $scheduled_at]);
}

header("Location: mail_queue.php");
exit;
?>

