<?php
require 'auth.php';
require 'dbconnect.php';

$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['user_role'];

if ($current_user_role === 'admin') {
    $stmt = $pdo->query("SELECT p.*, u.name AS user_name, u.email FROM projects p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC");
} else {
    $stmt = $pdo->prepare("SELECT p.*, u.name AS user_name, u.email FROM projects p JOIN users u ON p.user_id = u.id WHERE p.user_id = ? ORDER BY p.created_at DESC");
    $stmt->execute([$current_user_id]);
}
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f0f2f5;
      display: flex;
    }
    .sidebar {
      width: 220px;
      background-color: #343a40;
      color: white;
      height: 100vh;
      padding: 20px;
    }
    .sidebar h3 {
      color: #ffc107;
      margin-bottom: 20px;
    }
    .sidebar a {
      display: block;
      color: #ddd;
      text-decoration: none;
      padding: 10px 5px;
      border-radius: 4px;
    }
    .sidebar a:hover {
      background-color: #495057;
    }

    .main {
      flex: 1;
      padding: 30px;
    }
    h2 {
      color: #333;
    }
    .btn {
      padding: 8px 14px;
      text-decoration: none;
      border-radius: 4px;
      margin: 4px;
      display: inline-block;
    }
    .btn-create {
      background-color: #28a745;
      color: white;
    }
    .btn-action {
      background-color: #007bff;
      color: white;
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
    .empty {
      padding: 20px;
      text-align: center;
      color: #888;
      background: white;
      border: 1px dashed #ccc;
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
<?php if ($_SESSION['user_role'] == 1): ?>
  <div style="margin-top: 20px; background: #fff3cd; padding: 10px; border: 1px solid #ffeeba;">
    <h3>🔧 管理員功能</h3>
    <ul>
      <li><a href="admin_users.php">帳號管理</a></li>
      <li><a href="login_log.php">登入紀錄</a></li>
    </ul>
  </div>
<?php endif; ?>
  <div class="main">
    <h2>📋 我的專案列表</h2>

    <a href="create_project.php" class="btn btn-create">＋ 建立新專案</a>

    <?php if (count($projects) > 0): ?>
      <table>
        <thead>
          <tr>
            <th>名稱</th>
            <th>描述</th>
            <th>建立者</th>
            <th>建立時間</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($projects as $proj): ?>
            <tr>
              <td><?= htmlspecialchars($proj['name']) ?></td>
              <td><?= nl2br(htmlspecialchars($proj['description'])) ?></td>
              <td><?= htmlspecialchars($proj['user_name']) ?> (<?= htmlspecialchars($proj['email']) ?>)</td>
              <td><?= $proj['created_at'] ?></td>
              <td>
                <a href="#" class="btn btn-action">查看</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="empty">目前尚無專案，請先建立一個。</div>
    <?php endif; ?>
  </div>
</body>
</html>

