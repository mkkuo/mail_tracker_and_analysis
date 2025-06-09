<?php
require 'auth.php';
require_once 'csrf_guard.php'; // After auth.php
require 'dbconnect.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die(); // Verify CSRF for this POST action
    $type = $_POST['type'];
    $smtp_host = $_POST['smtp_host'];
    $smtp_port = $_POST['smtp_port'];
    $smtp_user = $_POST['smtp_user'];
    $smtp_pass = $_POST['smtp_pass'];
    $sender_name = $_POST['sender_name'];
    $sender_email = $_POST['sender_email'];
    $use_tls = isset($_POST['use_tls']) ? 1 : 0;

    $stmt = $pdo->prepare("SELECT id FROM mail_settings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE mail_settings SET type=?, smtp_host=?, smtp_port=?, smtp_user=?, smtp_pass=?, sender_name=?, sender_email=?, use_tls=? WHERE user_id=?");
        $stmt->execute([$type, $smtp_host, $smtp_port, $smtp_user, $smtp_pass, $sender_name, $sender_email, $use_tls, $user_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO mail_settings (user_id, type, smtp_host, smtp_port, smtp_user, smtp_pass, sender_name, sender_email, use_tls) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $type, $smtp_host, $smtp_port, $smtp_user, $smtp_pass, $sender_name, $sender_email, $use_tls]);
    }

    $msg = "設定已儲存 ✅";
}

// 讀取設定
$stmt = $pdo->prepare("SELECT * FROM mail_settings WHERE user_id = ?");
$stmt->execute([$user_id]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>寄信設定</title>
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
    label { display: block; margin-top: 15px; font-weight: bold; }
    input, select {
      padding: 6px;
      width: 400px;
      margin-top: 5px;
    }
    .btn {
      margin-top: 20px;
      padding: 10px 20px;
      background-color: #007bff;
      color: white;
      border: none;
      border-radius: 4px;
    }
    .msg {
      margin-top: 15px;
      color: green;
      font-weight: bold;
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
  <h2>⚙️ 寄信設定</h2>

  <?php if (!empty($msg)) echo "<div class='msg'>$msg</div>"; ?>

  <form method="post">
    <label>發信服務</label>
    <select name="type">
      <option value="gmail" <?= ($settings['type'] ?? '') === 'gmail' ? 'selected' : '' ?>>Gmail</option>
      <option value="outlook" <?= ($settings['type'] ?? '') === 'outlook' ? 'selected' : '' ?>>Outlook</option>
      <option value="mailgun" <?= ($settings['type'] ?? '') === 'mailgun' ? 'selected' : '' ?>>Mailgun</option>
      <option value="sendgrid" <?= ($settings['type'] ?? '') === 'sendgrid' ? 'selected' : '' ?>>SendGrid</option>
    </select>

    <label>SMTP 主機</label>
    <input type="text" name="smtp_host" value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>">

    <label>SMTP Port</label>
    <input type="number" name="smtp_port" value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>">

    <label>SMTP 帳號</label>
    <input type="text" name="smtp_user" value="<?= htmlspecialchars($settings['smtp_user'] ?? '') ?>">

    <label>SMTP 密碼</label>
    <input type="password" name="smtp_pass" value="<?= htmlspecialchars($settings['smtp_pass'] ?? '') ?>">

    <label>寄件人名稱</label>
    <input type="text" name="sender_name" value="<?= htmlspecialchars($settings['sender_name'] ?? '') ?>">

    <label>寄件人 Email</label>
    <input type="email" name="sender_email" value="<?= htmlspecialchars($settings['sender_email'] ?? '') ?>">

    <label><input type="checkbox" name="use_tls" <?= ($settings['use_tls'] ?? 1) ? 'checked' : '' ?>> 使用 TLS</label>
    <?php csrf_input_field(); ?>
    <button type="submit" class="btn">儲存設定</button>
  </form>
</div>

</body>
</html>

