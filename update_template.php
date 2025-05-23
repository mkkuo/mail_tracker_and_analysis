<?php
require 'auth.php';
require 'dbconnect.php';

$template_id = $_POST['id'] ?? null;
$subject = $_POST['subject'] ?? '';
$project_id = $_POST['project_id'] ?? null;
$content = $_POST['content'] ?? '';
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

if (!$template_id || !$project_id || trim($subject) === '' || trim($content) === '') {
    die("❌ 所有欄位皆為必填！");
}

// 先查原範本、確認權限
$stmt = $pdo->prepare("SELECT t.*, p.user_id AS project_owner FROM templates t JOIN projects p ON t.project_id = p.id WHERE t.id = ?");
$stmt->execute([$template_id]);
$template = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$template) {
    die("❌ 找不到該範本。");
}
if ($user_role !== 'admin' && $template['project_owner'] != $user_id) {
    die("❌ 無權修改此範本。");
}

// 🔍 移除既有追蹤碼 <img src="track_open.php?...">
$content = preg_replace('/<img[^>]*src="track_open\.php[^"]*"[^>]*>/i', '', $content);

// 🔁 重新插入追蹤碼
$tracking_img = '<img src="track_open.php?pid=' . urlencode($project_id) . '&uid=' . urlencode($user_id) . '" width="1" height="1" style="display:none;">';
if (stripos($content, '</body>') !== false) {
    $content = str_ireplace('</body>', $tracking_img . '</body>', $content);
} else {
    $content .= $tracking_img;
}

// ✏️ 更新內容
$stmt = $pdo->prepare("UPDATE templates SET subject = ?, project_id = ?, content = ? WHERE id = ?");
$stmt->execute([$subject, $project_id, $content, $template_id]);

header("Location: templates.php");
exit;
?>

