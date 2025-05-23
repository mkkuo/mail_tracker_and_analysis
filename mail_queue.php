<?php
require 'auth.php';
require 'dbconnect.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

if ($user_role === 'admin') {
    $stmt = $pdo->query("
        SELECT q.*, 
               p.name AS project_name, 
               r.email AS recipient_email, 
               t.subject AS mail_subject,
               p.user_id AS project_owner
        FROM mail_queue q
        JOIN recipients r ON q.recipient_id = r.id
        JOIN projects p ON q.project_id = p.id
        LEFT JOIN templates t ON t.project_id = p.id
        ORDER BY q.scheduled_at DESC
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT q.*, 
               p.name AS project_name, 
               r.email AS recipient_email, 
               t.subject AS mail_subject
        FROM mail_queue q
        JOIN recipients r ON q.recipient_id = r.id
        JOIN projects p ON q.project_id = p.id
        LEFT JOIN templates t ON t.project_id = p.id
        WHERE p.user_id = ?
        ORDER BY q.scheduled_at DESC
    ");
    $stmt->execute([$user_id]);
}
$queues = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>郵件排程清單</title>
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
      padding: 10px;
      border: 1px solid #ccc;
      text-align: left;
    }
    th {
      background-color: #e9ecef;
    }
    .status-pending { color: orange; font-weight: bold; }
    .status-sent { color: green; font-weight: bold; }
    .status-failed { color: red; font-weight: bold; }
    .btn {
      padding: 6px 12px;
      background-color: #dc3545;
      color: white;
      border-radius: 4px;
      text-decoration: none;
      font-size: 14px;
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
  <h2>📤 郵件排程清單</h2>

  <?php if (count($queues) > 0): ?>
    <table>
      <thead>
        <tr>
          <th>專案名稱</th>
          <th>收件人</th>
          <th>郵件標題</th>
          <th>預定時間</th>
          <th>實際寄送時間</th>
          <th>狀態</th>
          <th>錯誤訊息</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($queues as $q): ?>
          <tr>
            <td><?= htmlspecialchars($q['project_name']) ?></td>
            <td><?= htmlspecialchars($q['recipient_email']) ?></td>
            <td><?= htmlspecialchars($q['mail_subject'] ?? '（未指定）') ?></td>
            <td><?= $q['scheduled_at'] ?></td>
            <td><?= $q['sent_at'] ?? '-' ?></td>
            <td class="status-<?= $q['status'] ?>">
              <?= strtoupper($q['status']) ?>
            </td>
            <td><?= htmlspecialchars($q['error'] ?? '-') ?></td>
            <td>
              <?php if ($q['status'] === 'pending'): ?>
                <a class="btn" href="delete_queue.php?id=<?= $q['id'] ?>" onclick="return confirm('確定要刪除這筆排程？')">刪除</a>
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p>目前尚無排程。</p>
  <?php endif; ?>
</div>

</body>
</html>

