<?php
require 'auth.php';
require 'dbconnect.php';

if ($_SESSION['user_role'] != 1) {
  echo "❌ 無權限進入此頁";
  exit;
}

$email = $_GET['email'] ?? '';
$params = [];
$where = "1=1";

if ($email) {
  $where .= " AND email LIKE ?";
  $params[] = "%$email%";
}

$stmt = $pdo->prepare("SELECT * FROM login_log WHERE $where ORDER BY logged_at DESC LIMIT 200");
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>登入紀錄</title>
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
    input[type="text"] {
      padding: 6px;
      width: 250px;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>

<div class="sidebar">
  <h3>📬 MailPanel</h3>
  <a href="dashboard.php">🏠 專案總覽</a>
  <a href="admin_users.php">👤 帳號管理</a>
  <a href="login_log.php">🕓 登入紀錄</a>
  <a href="logout.php">🚪 登出</a>
</div>

<div class="main">
  <h2>🕓 登入紀錄查詢</h2>

  <form method="get">
    <label>Email 關鍵字：</label>
    <input type="text" name="email" value="<?= htmlspecialchars($email) ?>">
    <button type="submit">搜尋</button>
  </form>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Email</th>
        <th>IP</th>
        <th>登入時間</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($logs as $log): ?>
        <tr>
          <td><?= $log['id'] ?></td>
          <td><?= htmlspecialchars($log['email']) ?></td>
          <td><?= $log['ip'] ?></td>
          <td><?= $log['logged_at'] ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

</body>
</html>

