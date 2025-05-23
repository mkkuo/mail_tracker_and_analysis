<?php
require 'auth.php';
require 'dbconnect.php';

$project_id = $_POST['project_id'] ?? null;
$subject = $_POST['subject'] ?? '';
$content = $_POST['content'] ?? '';
$user_id = $_SESSION['user_id'];

// 驗證基本欄位
if (!$project_id || trim($subject) === '' || trim($content) === '') {
    die("❌ 所有欄位皆為必填！");
}

// 自動插入開信追蹤碼（可支援延伸版本）
$tracking_img = '<img src="track_open.php?pid=' . urlencode($project_id) . '&uid=' . urlencode($user_id) . '" width="1" height="1" style="display:none;">';

// 插入 tracking image 到 </body> 前，若沒有 <body> 就加在最尾巴
if (stripos($content, '</body>') !== false) {
    $content = str_ireplace('</body>', $tracking_img . '</body>', $content);
} else {
    $content .= $tracking_img;
}

// 寫入資料庫
$stmt = $pdo->prepare("INSERT INTO templates (project_id, subject, content) VALUES (?, ?, ?)");
$stmt->execute([$project_id, $subject, $content]);

// 導回清單頁（之後可以建立 templates.php 顯示所有信件）
header("Location: templates.php");
exit;
?>

