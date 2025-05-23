<?php
require 'auth.php';
require 'dbconnect.php';

if ($_SESSION['user_role'] != 1) {
  echo "❌ 無權存取此頁面。";
  exit;
}

$msg = '';

// 新增帳號
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
  $email = $_POST['email'];
  $password = $_POST['password'];
  $atype = intval($_POST['atype']);

  if (filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($password) >= 4) {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (email, password, atype, status) VALUES (?, ?, ?, 1)");
    $stmt->execute([$email, $hashed, $atype]);
    $msg = "✅ 帳號已新增！";
  } else {
    $msg = "❌ Email 格式錯誤或密碼太短！";
  }
}

// 取得帳號
$stmt = $pdo->query("SELECT id, email, atype, status, created_at FROM users ORDER BY id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 角色對應
$role_map = [
  1 => '管理者',
  3 => '銷售客服',
  5 => '內部協作',
  7 => '一般使用者'
];
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>帳號管理</title>
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
    label { display: block; margin-top: 15px; font-weight: bold; }
    input, select {
      width: 400px; padding: 6px; margin-top: 5px;
    }
    .btn {
      margin-top: 15px; padding: 8px 20px; background-color: #007bff;
      color: white; border: none; border-radius: 4px;
    }
    .btn-danger { background-color: #dc3545; }
    .btn-sm { padding: 4px 8px; font-size: 13px; margin-right: 5px; }
    .msg { margin-top: 10px; font-weight: bold; color: green; }
    table {
      margin-top: 30px; width: 100%; border-collapse: collapse; background: white;
    }
    th, td {
      padding: 8px; border: 1px solid #ccc; text-align: left;
    }
    th { background-color: #e9ecef; }
    .inactive { background: #f8d7da; }
  </style>
</head>
<body>

<div class="sidebar">
  <h3>📬 MailPanel</h3>
  <a href="dashboard.php">🏠 專案總覽</a>
  <a href="admin_users.php">👤 帳號管理</a>
  <a href="logout.php">🚪 登出</a>
</div>

<div class="main">
  <h2>👤 帳號管理</h2>

  <?php if ($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>

  <form method="post">
    <input type="hidden" name="action" value="add">
    <label>Email</label>
    <input type="email" name="email" required>

    <label>密碼</label>
    <input type="password" name="password" required>

    <label>角色類型</label>
    <select name="atype" required>
      <option value="1">管理者</option>
      <option value="3">銷售 / 客服</option>
      <option value="5">內部協作者</option>
      <option value="7">一般使用者</option>
    </select>

    <button type="submit" class="btn">➕ 新增帳號</button>
  </form>

  <h3>帳號列表</h3>
  <table>
    <tr>
      <th>ID</th>
      <th>Email</th>
      <th>角色</th>
      <th>狀態</th>
      <th>建立時間</th>
      <th>操作</th>
    </tr>
    <?php foreach ($users as $u): ?>
      <tr class="<?= $u['status'] == 0 ? 'inactive' : '' ?>">
        <td><?= $u['id'] ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><?= $role_map[$u['atype']] ?? '未知' ?></td>
        <td><?= $u['status'] ? '✅ 啟用' : '⛔ 已停用' ?></td>
        <td><?= $u['created_at'] ?></td>
        <td>
          <form method="post" action="admin_users_update.php" style="display:inline;">
            <input type="hidden" name="uid" value="<?= $u['id'] ?>">
            <input type="hidden" name="action" value="toggle">
            <button class="btn btn-sm <?= $u['status'] ? 'btn-danger' : '' ?>">
              <?= $u['status'] ? '停用' : '啟用' ?>
            </button>
          </form>
          <form method="post" action="admin_users_update.php" style="display:inline;">
            <input type="hidden" name="uid" value="<?= $u['id'] ?>">
            <input type="hidden" name="action" value="edit">
            <button class="btn btn-sm">編輯</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

</body>
</html>

