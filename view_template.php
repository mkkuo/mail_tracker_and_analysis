<?php
require 'auth.php';
require 'dbconnect.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$template_id = $_GET['id'] ?? null;

if (!$template_id) {
    die("❌ 未指定範本 ID。");
}

// 查詢範本內容與專案
$stmt = $pdo->prepare("SELECT t.*, p.name AS project_name, p.user_id AS project_owner
                       FROM templates t
                       JOIN projects p ON t.project_id = p.id
                       WHERE t.id = ?");
$stmt->execute([$template_id]);
$template = $stmt->fetch(PDO::FETCH_ASSOC);

// 權限檢查
if (!$template) {
    die("❌ 找不到該範本。");
}
if ($user_role !== 'admin' && $template['project_owner'] != $user_id) {
    die("❌ 無權查看此範本。");
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>查看郵件範本</title>
  <style>
    body { margin: 0; font-family: Arial, sans-serif; background: #f0f2f5; display: flex; }
    .sidebar {
      width: 220px;
      background-color: #343a40;
      color: white;
      height: 100vh;
      padding: 20px;
    }
    .sidebar h3 { color: #ffc107; margin-bottom: 20px; }
    .sidebar a {
      display: block;
      color: #ddd;
      text-decoration: none;
      padding: 10px 5px;
      border-radius: 4px;
    }
    .sidebar a:hover { background-color: #495057; }
    .main {
      flex: 1;
      padding: 30px;
    }
    .meta {
      margin-bottom: 20px;
      background: #fff;
      padding: 15px;
      border-radius: 6px;
    }
    .content {
      background: #fff;
      border: 1px solid #ccc;
      padding: 20px;
      min-height: 200px;
      border-radius: 6px;
    }
  </style>
</head>
<body>

<div class="sidebar">
  <h3>📬 MailPanel</h3>
  <a href="dashboard.php">🏠 專案總覽</a>
  <a href="templates.php">✉️ 郵件範本</a>
  <a href="create_template.php">📝 建立新範本</a>
  <a href="recipients.php">📂 寄送名單上傳</a>
  <a href="mail_queue.php">📤 寄送排程</a>
  <a href="report.php">📊 測試成果報告</a>
  <a href="log.php">📑 行為紀錄</a>
  <a href="settings.php">⚙️ 寄信設定</a>
  <a href="logout.php">🚪 登出</a>
</div>

<div class="main">
  <h2>✉️ 信件內容預覽</h2>

  <div class="meta">
    <p><strong>標題：</strong> <?= htmlspecialchars($template['subject']) ?></p>
    <p><strong>所屬專案：</strong> <?= htmlspecialchars($template['project_name']) ?></p>
    <p><strong>建立時間：</strong> <?= htmlspecialchars($template['created_at']) ?></p>
  </div>

  <div class="content">
    <?= $template['content'] ?>
  </div>

</div>

</body>
</html>

