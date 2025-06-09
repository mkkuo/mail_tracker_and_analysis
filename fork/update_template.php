<?php
require 'auth.php';
require_once 'csrf_guard.php'; // Needs to be after auth.php
verify_csrf_or_die(); // Call this early
require 'dbconnect.php';
require_once __DIR__ . '/vendor/autoload.php';

$template_id = $_POST['id'] ?? null;
$subject = $_POST['subject'] ?? '';
$project_id = $_POST['project_id'] ?? null;
$raw_content = $_POST['content'] ?? ''; // Renamed
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

if (!$template_id || !$project_id || trim($subject) === '' || trim($raw_content) === '') {
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

// 🔍 移除既有追蹤碼 <img src="track_open.php?..."> from the raw content
$content_without_old_pixel = preg_replace('/<img[^>]*src="track_open\.php[^"]*"[^>]*>/i', '', $raw_content);

// Initialize HTMLPurifier
$config = HTMLPurifier_Config::createDefault();
$config->set('Cache.SerializerPath', __DIR__ . '/purifier_cache');
// Add more configuration if needed
$purifier = new HTMLPurifier($config);
$clean_content = $purifier->purify($content_without_old_pixel);

// 🔁 重新插入追蹤碼 to the cleaned content
$tracking_img = '<img src="track_open.php?pid=' . urlencode($project_id) . '&uid=' . urlencode($user_id) . '" width="1" height="1" style="display:none;">';
if (stripos($clean_content, '</body>') !== false) {
    $content_with_new_pixel = str_ireplace('</body>', $tracking_img . '</body>', $clean_content);
} else {
    $content_with_new_pixel = $clean_content . $tracking_img;
}

// ✏️ 更新內容
$stmt = $pdo->prepare("UPDATE templates SET subject = ?, project_id = ?, content = ? WHERE id = ?");
$stmt->execute([$subject, $project_id, $content_with_new_pixel, $template_id]);

header("Location: templates.php");
exit;
?>

