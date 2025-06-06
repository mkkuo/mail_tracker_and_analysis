<?php
require_once 'auth.php';
require_once 'dbconnect.php';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="project_report.csv"');

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$project_id = $_GET['project_id'] ?? null;

if (!$project_id) {
    echo "Invalid request";
    exit;
}

// 權限檢查
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project || ($user_role !== 'admin' && $project['user_id'] != $user_id)) {
    echo "Unauthorized";
    exit;
}

// 統計資料
function get_stat($pdo, $sql, $params) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

$sent   = get_stat($pdo, "SELECT COUNT(*) AS total, COUNT(DISTINCT recipient_id) AS users FROM mail_queue WHERE status='sent' AND project_id = ?", [$project_id]);
$opens  = get_stat($pdo, "SELECT COUNT(*) AS total, COUNT(DISTINCT recipient_id) AS users FROM mail_open_log WHERE project_id = ?", [$project_id]);
$clicks = get_stat($pdo, "SELECT COUNT(*) AS total, COUNT(DISTINCT recipient_id) AS users FROM mail_click_log WHERE project_id = ?", [$project_id]);

function rate($a, $b) {
    return ($b > 0) ? round($a / $b * 100, 2) : 0;
}

// 輸出 CSV
$output = fopen('php://output', 'w');
fputs($output, "\xEF\xBB\xBF"); // BOM for UTF-8 Excel

fputcsv($output, ['專案 ID', '專案名稱', '寄信總數', '寄信人數', '開信人次', '開信人數', '開信人次率 (%)', '開信人數率 (%)', '點擊人次', '點擊人數', '點擊人次率 (%)', '點擊人數率 (%)']);
fputcsv($output, [
    $project['id'],
    $project['name'],
    $sent['total'],
    $sent['users'],
    $opens['total'],
    $opens['users'],
    rate($opens['total'], $sent['total']),
    rate($opens['users'], $sent['users']),
    $clicks['total'],
    $clicks['users'],
    rate($clicks['total'], $sent['total']),
    rate($clicks['users'], $sent['users']),
]);

fclose($output);
exit;

