<?php
require 'auth.php';
require 'dbconnect.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

if ($user_role === 'admin') {
    $stmt = $pdo->query("SELECT t.*, p.name AS project_name FROM templates t JOIN projects p ON t.project_id = p.id ORDER BY t.created_at DESC");
} else {
    $stmt = $pdo->prepare("SELECT t.*, p.name AS project_name FROM templates t JOIN projects p ON t.project_id = p.id WHERE p.user_id = ? ORDER BY t.created_at DESC");
    $stmt->execute([$user_id]);
}
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>郵件範本清單</title>
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
    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      margin-top: 20px;
    }
    th, td {
      padding: 12px;
      border: 1px solid #dee2e6;
      text-align: left;
    }
    th {
      background-color: #e9ecef;
    }
    .btn {
      padding: 6px 12px;
      background-color: #007bff;
      color: white;
      border-radius: 4px;
      text-decoration: none;
      margin-right: 5px;
    }
    .empty {
      padding: 20px;
      background: white;
      border: 1px dashed #ccc;
      text-align: center;
      color: #777;
    }
  </style>
</head>
<body>

<div class="sidebar">
  <h3>📬 MailPanel</h3>
  <a href="dashboard.php">🏠 專案總覽</a>
  <a href="create_project.php">➕ 建立專案</a>
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
  <h2>✉️ 郵件範本清單</h2>

  <?php if (count($templates) > 0): ?>
    <table>
      <thead>
        <tr>
          <th>所屬專案</th>
          <th>標題</th>
          <th>建立時間</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($templates as $tpl): ?>
          <tr>
            <td><?= htmlspecialchars($tpl['project_name']) ?></td>
            <td><?= htmlspecialchars($tpl['subject']) ?></td>
            <td><?= $tpl['created_at'] ?></td>
            <td>
              <a class="btn" href="view_template.php?id=<?= $tpl['id'] ?>">查看</a>
              <a class="btn" href="edit_template.php?id=<?= $tpl['id'] ?>">編輯</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="empty">目前尚無郵件範本，請先建立。</div>
  <?php endif; ?>
</div>

</body>
</html>

