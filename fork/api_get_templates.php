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

$stmt = $pdo->prepare("SELECT id, subject FROM templates WHERE project_id = ? ORDER BY created_at DESC");
$stmt->execute([$project_id]);
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($templates) === 0) {
    echo '<option value="">-- 尚無範本 --</option>';
} else {
    echo '<option value="">-- 請選擇範本 --</option>';
    foreach ($templates as $tpl) {
        echo '<option value="' . $tpl['id'] . '">' . htmlspecialchars($tpl['subject']) . '</option>';
    }
}

