<?php
require_once 'auth.php';
require_once 'dbconnect.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$project_id = $_POST['project_id'] ?? null;

if (!$project_id) {
    echo '<option value="">-- 無資料 --</option>';
    exit;
}

// 權限檢查
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project || ($user_role !== 'admin' && $project['user_id'] != $user_id)) {
    echo '<option value="">-- 無權存取 --</option>';
    exit;
}

$stmt = $pdo->prepare("SELECT id, name, email FROM recipients WHERE project_id = ? ORDER BY id DESC");
$stmt->execute([$project_id]);
$recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($recipients) === 0) {
    echo '<option value="">-- 尚無收件人 --</option>';
} else {
    foreach ($recipients as $r) {
        echo '<option value="' . $r['id'] . '">' . htmlspecialchars($r['name']) . ' (' . htmlspecialchars($r['email']) . ')</option>';
    }
}

