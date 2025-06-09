<?php
require_once 'auth.php';
require_once 'dbconnect.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// 取得搜尋條件
$email = $_GET['email'] ?? '';
$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';

$where = "1=1";
$params = [];

if ($user_role !== 'admin') {
  $where .= " AND o.user_id = ?";
  $params[] = $user_id;
}
if ($email) {
  $where .= " AND r.email LIKE ?";
  $params[] = "%$email%";
}
if ($start) {
  $where .= " AND o.opened_at >= ?";
  $params[] = $start . ' 00:00:00';
}
if ($end) {
  $where .= " AND o.opened_at <= ?";
  $params[] = $end . ' 23:59:59';
}

// 開信紀錄
$stmt = $pdo->prepare("
  SELECT o.*, r.email AS recipient_email, p.name AS project_name
  FROM mail_open_log o
  JOIN recipients r ON o.recipient_id = r.id
  JOIN projects p ON o.project_id = p.id
  WHERE $where
  ORDER BY o.opened_at DESC
");
$stmt->execute($params);
$opens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 點擊紀錄條件複製 & 替換 o -> c
$where_c = str_replace("o.", "c.", $where);
$where_c = str_replace("opened_at", "clicked_at", $where_c);

$stmt = $pdo->prepare("
  SELECT c.*, r.email AS recipient_email, p.name AS project_name
  FROM mail_click_log c
  JOIN recipients r ON c.recipient_id = r.id
  JOIN projects p ON c.project_id = p.id
  WHERE $where_c
  ORDER BY c.clicked_at DESC
");
$stmt->execute($params);
$clicks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>📑 開信與點擊紀錄</title>
  <style>
    body { margin: 0; font-family: Arial, sans-serif; background: #f0f2f5; display: flex; }
    .sidebar {
      width: 220px; background-color: #343a40; color: white; height: 100vh; padding: 20px;
    }
    .sidebar h3 { color: #ffc107; margin-bottom: 20px; }
    .sidebar a {
      display: block; color: #ddd; text-decoration: none; padding: 10px 5px; border-radius: 4px;
    }
    .sidebar a:hover { background-color: #495057; }
    .main {
      flex: 1; padding: 30px;
    }
    table {
      width: 100%; border-collapse: collapse; background: white; margin-top: 20px;
    }
    th, td {
      padding: 8px; border: 1px solid #ccc; text-align: left;
    }
    th { background-color: #e9ecef; }
    .section { margin-bottom: 40px; }
    input[type="text"], input[type="date"] {
      padding: 6px; margin-right: 10px;
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
  <a href="schedule_mail.php">📅 建立排程</a>
  <a href="mail_queue.php">📤 寄送排程</a>
  <a href="report.php">📊 測試成果報告</a>
  <a href="log.php">📑 行為紀錄</a>
  <a href="settings.php">⚙️ 寄信設定</a>
  <a href="logout.php">🚪 登出</a>
</div>

<div class="main">
  <h2>📑 行為紀錄查詢</h2>

  <form method="get">
    收件人 Email：<input type="text" name="email" value="<?= htmlspecialchars($email) ?>">
    起始日期：<input type="date" name="start" value="<?= htmlspecialchars($start) ?>">
    結束日期：<input type="date" name="end" value="<?= htmlspecialchars($end) ?>">
    <button type="submit">🔍 查詢</button>
  </form>

  <div class="section">
    <h3>👁️ 開信紀錄</h3>
    <?php if (count($opens)): ?>
      <table>
        <tr>
          <th>專案</th>
          <th>收件人</th>
          <th>開啟時間</th>
          <th>IP</th>
          <th>裝置 / 瀏覽器</th>
        </tr>
        <?php foreach ($opens as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['project_name']) ?></td>
            <td><?= htmlspecialchars($row['recipient_email']) ?></td>
            <td><?= $row['opened_at'] ?></td>
            <td><?= $row['ip'] ?></td>
            <td><?= htmlspecialchars($row['user_agent']) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php else: ?>
      <p>尚無開信紀錄。</p>
    <?php endif; ?>
  </div>

  <div class="section">
    <h3>🔗 點擊紀錄</h3>
    <?php if (count($clicks)): ?>
      <table>
        <tr>
          <th>專案</th>
          <th>收件人</th>
          <th>點擊時間</th>
          <th>網址</th>
          <th>IP</th>
          <th>裝置 / 瀏覽器</th>
        </tr>
        <?php foreach ($clicks as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['project_name']) ?></td>
            <td><?= htmlspecialchars($row['recipient_email']) ?></td>
            <td><?= $row['clicked_at'] ?></td>
            <td><a href="<?= htmlspecialchars($row['clicked_url']) ?>" target="_blank"><?= htmlspecialchars($row['clicked_url']) ?></a></td>
            <td><?= $row['ip'] ?></td>
            <td><?= htmlspecialchars($row['user_agent']) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php else: ?>
      <p>尚無點擊紀錄。</p>
    <?php endif; ?>
  </div>
</div>

</body>
</html>

