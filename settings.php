<?php
require 'auth.php';
require 'dbconnect.php';

// Table for provider-specific SMTP settings
$pdo->exec("CREATE TABLE IF NOT EXISTS smtp_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    provider VARCHAR(20) NOT NULL,
    smtp_host VARCHAR(255),
    smtp_port INT,
    smtp_user VARCHAR(255),
    smtp_pass VARCHAR(255),
    sender_name VARCHAR(255),
    sender_email VARCHAR(255),
    use_tls TINYINT(1) DEFAULT 1,
    UNIQUE KEY user_provider (user_id, provider)
)");

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $smtp_host = $_POST['smtp_host'];
    $smtp_port = $_POST['smtp_port'];
    $smtp_user = $_POST['smtp_user'];
    $smtp_pass = $_POST['smtp_pass'];
    $sender_name = $_POST['sender_name'];
    $sender_email = $_POST['sender_email'];
    $use_tls = isset($_POST['use_tls']) ? 1 : 0;

    // Save active provider
    $stmt = $pdo->prepare("SELECT id FROM mail_settings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE mail_settings SET type=? WHERE user_id=?");
        $stmt->execute([$type, $user_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO mail_settings (user_id, type) VALUES (?, ?)");
        $stmt->execute([$user_id, $type]);
    }

    // Save SMTP account for selected provider
    $stmt = $pdo->prepare("SELECT id FROM smtp_accounts WHERE user_id=? AND provider=?");
    $stmt->execute([$user_id, $type]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE smtp_accounts SET smtp_host=?, smtp_port=?, smtp_user=?, smtp_pass=?, sender_name=?, sender_email=?, use_tls=? WHERE user_id=? AND provider=?");
        $stmt->execute([$smtp_host, $smtp_port, $smtp_user, $smtp_pass, $sender_name, $sender_email, $use_tls, $user_id, $type]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO smtp_accounts (user_id, provider, smtp_host, smtp_port, smtp_user, smtp_pass, sender_name, sender_email, use_tls) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $type, $smtp_host, $smtp_port, $smtp_user, $smtp_pass, $sender_name, $sender_email, $use_tls]);
    }

    $msg = "設定已儲存 ✅";
}

// 讀取設定
$stmt = $pdo->prepare("SELECT * FROM mail_settings WHERE user_id = ?");
$stmt->execute([$user_id]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

$current_type = $_GET['provider'] ?? ($settings['type'] ?? 'gmail');
$stmt = $pdo->prepare("SELECT * FROM smtp_accounts WHERE user_id=? AND provider=?");
$stmt->execute([$user_id, $current_type]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

$defaults = [
    'gmail' => ['smtp_host' => 'smtp.gmail.com', 'smtp_port' => 587, 'use_tls' => 1],
    'outlook' => ['smtp_host' => 'smtp.office365.com', 'smtp_port' => 587, 'use_tls' => 1],
];
if (!$account) {
    $account = $defaults[$current_type] ?? [];
}
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
@@ -82,59 +122,64 @@ $settings = $stmt->fetch(PDO::FETCH_ASSOC);
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
    <select name="type" id="provider-select">
      <option value="gmail" <?= $current_type === 'gmail' ? 'selected' : '' ?>>Gmail</option>
      <option value="outlook" <?= $current_type === 'outlook' ? 'selected' : '' ?>>Outlook</option>
    </select>

    <label>SMTP 主機</label>
    <input type="text" name="smtp_host" value="<?= $account['smtp_host'] ?? '' ?>">

    <label>SMTP Port</label>
    <input type="number" name="smtp_port" value="<?= $account['smtp_port'] ?? '587' ?>">

    <label>SMTP 帳號</label>
    <input type="text" name="smtp_user" value="<?= $account['smtp_user'] ?? '' ?>">

    <label>SMTP 密碼</label>
    <input type="password" name="smtp_pass" value="<?= $account['smtp_pass'] ?? '' ?>">

    <label>寄件人名稱</label>
    <input type="text" name="sender_name" value="<?= $account['sender_name'] ?? '' ?>">

    <label>寄件人 Email</label>
    <input type="email" name="sender_email" value="<?= $account['sender_email'] ?? '' ?>">

    <label><input type="checkbox" name="use_tls" <?= ($account['use_tls'] ?? 1) ? 'checked' : '' ?>> 使用 TLS</label>

  <button type="submit" class="btn">儲存設定</button>
  </form>
</div>

<script>
document.getElementById('provider-select').addEventListener('change', function() {
    var p = this.value;
    window.location.href = 'settings.php?provider=' + p;
});
</script>

</body>
</html>