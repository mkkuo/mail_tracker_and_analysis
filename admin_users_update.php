<?php
require_once 'auth.php';
require_once 'csrf_guard.php'; // After auth.php
require_once 'dbconnect.php';

if ($_SESSION['user_role'] != 1) {
  echo "❌ 無權操作此功能";
  exit;
}

// IMPORTANT: Verify CSRF for ALL POST actions handled by this script.
// Needs to be called after session is available (auth.php) and before POST data is processed.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();
}

$action = $_POST['action'] ?? $_GET['action'] ?? ''; // Edit action might come via GET then POST
$uid = intval($_POST['uid'] ?? $_GET['uid'] ?? 0); // uid can also come from GET for edit display

// 切換啟用/停用帳號
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

// 顯示帳號編輯畫面
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
    <form method="post" action="admin_users_update.php">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="uid" value="<?= $uid ?>">

      <label>Email (只讀)</label>
      <input type="email" value="<?= $email ?>" disabled>

      <label>新密碼（如不修改請留空）</label>
      <input type="password" name="password">

      <label>角色類型</label>
      <select name="atype">
        <option value="1" <?= $atype == 1 ? 'selected' : '' ?>>管理者</option>
        <option value="3" <?= $atype == 3 ? 'selected' : '' ?>>銷售客服</option>
        <option value="5" <?= $atype == 5 ? 'selected' : '' ?>>內部協作</option>
        <option value="7" <?= $atype == 7 ? 'selected' : '' ?>>一般使用者</option>
      </select>
      <?php csrf_input_field(); ?>
      <button type="submit" class="btn">💾 儲存修改</button>
    </form>
  </body>
  </html>

  <?php
  exit;
}

// 儲存編輯資料
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

