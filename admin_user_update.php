<?php
require 'auth.php';
require 'dbconnect.php';

if ($_SESSION['user_role'] != 1) {
  echo "❌ 無權操作";
  exit;
}

$action = $_POST['action'] ?? '';
$uid = intval($_POST['uid'] ?? 0);

// 啟用 / 停用帳號
if ($action === 'toggle') {
  $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
  $stmt->execute([$uid]);
  $user = $stmt->fetch();
  if ($user) {
    $new_status = $user['status'] == 1 ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $uid]);
  }
  header("Location: admin_users.php");
  exit;
}

// 顯示編輯表單
if ($action === 'edit') {
  $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->execute([$uid]);
  $user = $stmt->fetch();

  if (!$user) {
    echo "找不到帳號";
    exit;
  }

  $atype = $user['atype'];
  $email = htmlspecialchars($user['email']);
  ?>

  <!DOCTYPE html>
  <html>
  <head>
    <meta charset="UTF-8">
    <title>編輯帳號</title>
    <style>
      body { font-family: Arial; padding: 40px; background: #f8f9fa; }
      form { background: white; padding: 30px; width: 400px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
      label { display: block; margin-top: 15px; }
      input, select { width: 100%; padding: 6px; }
      .btn { margin-top: 20px; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; }
    </style>
  </head>
  <body>
    <h2>✏️ 編輯帳號</h2>
    <form method="post">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="uid" value="<?= $uid ?>">

      <label>Email (只讀)</label>
      <input type="email" value="<?= $email ?>" disabled>

      <label>新密碼（若不修改請留空）</label>
      <input type="password" name="password">

      <label>角色類型</label>
      <select name="atype">
        <option value="1" <?= $atype == 1 ? 'selected' : '' ?>>管理者</option>
        <option value="3" <?= $atype == 3 ? 'selected' : '' ?>>銷售客服</option>
        <option value="5" <?= $atype == 5 ? 'selected' : '' ?>>內部協作</option>
        <option value="7" <?= $atype == 7 ? 'selected' : '' ?>>一般使用者</option>
      </select>

      <button type="submit" class="btn">💾 儲存修改</button>
    </form>
  </body>
  </html>

  <?php
  exit;
}

// 儲存編輯
if ($action === 'save') {
  $atype = intval($_POST['atype']);
  $password = $_POST['password'] ?? '';

  if ($password) {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ?, atype = ? WHERE id = ?");
    $stmt->execute([$hashed, $atype, $uid]);
  } else {
    $stmt = $pdo->prepare("UPDATE users SET atype = ? WHERE id = ?");
    $stmt->execute([$atype, $uid]);
  }

  header("Location: admin_users.php");
  exit;
}
?>

