<?php
require_once 'auth.php';
require_once 'csrf_guard.php'; // Needs to be after auth.php
verify_csrf_or_die(); // Call this early
require_once 'dbconnect.php';
require_once __DIR__ . '/vendor/autoload.php';

$project_id = $_POST['project_id'] ?? null;
$subject = $_POST['subject'] ?? '';
$raw_content = $_POST['content'] ?? ''; // Renamed to avoid confusion
$user_id = $_SESSION['user_id'];

// 驗證基本欄位
if (!$project_id || trim($subject) === '' || trim($raw_content) === '') {
    die("❌ 所有欄位皆為必填！");
}

// Initialize HTMLPurifier
$config = HTMLPurifier_Config::createDefault();
$config->set('Cache.SerializerPath', __DIR__ . '/purifier_cache');
// Add more configuration if needed, e.g., allowed elements/attributes
// $config->set('HTML.Allowed', 'p,a[href],img[src]'); 
$purifier = new HTMLPurifier($config);
$clean_content = $purifier->purify($raw_content);

// 自動插入開信追蹤碼（可支援延伸版本）
$tracking_img = '<img src="track_open.php?pid=' . urlencode($project_id) . '&uid=' . urlencode($user_id) . '" width="1" height="1" style="display:none;">';

// 插入 tracking image 到 </body> 前，若沒有 <body> 就加在最尾巴
if (stripos($clean_content, '</body>') !== false) {
    $content_with_pixel = str_ireplace('</body>', $tracking_img . '</body>', $clean_content);
} else {
    $content_with_pixel = $clean_content . $tracking_img;
}

// 寫入資料庫
$stmt = $pdo->prepare("INSERT INTO templates (project_id, subject, content) VALUES (?, ?, ?)");
$stmt->execute([$project_id, $subject, $content_with_pixel]);

// 導回清單頁（之後可以建立 templates.php 顯示所有信件）
header("Location: templates.php");
exit;
?>

