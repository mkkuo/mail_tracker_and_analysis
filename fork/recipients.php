<?php
require 'auth.php';
require_once 'csrf_guard.php'; // After auth.php
require 'dbconnect.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$msg = '';
$msg_type = ''; // Initialize message type

// 專案清單
if ($user_role === 'admin') {
  $stmt = $pdo->query("SELECT id, name FROM projects ORDER BY created_at DESC");
} else {
  $stmt = $pdo->prepare("SELECT id, name FROM projects WHERE user_id = ?");
  $stmt->execute([$user_id]);
}
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// CSV 上傳
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv']) && isset($_POST['project_id'])) {
  verify_csrf_or_die(); // Verify CSRF for this POST action
  $project_id = $_POST['project_id'];

  if (isset($_FILES['csv']['error']) && $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
    $msg_type = 'error';
    switch ($_FILES['csv']['error']) {
      case UPLOAD_ERR_INI_SIZE:
      case UPLOAD_ERR_FORM_SIZE:
        $msg = "❌ 檔案過大，超過伺服器或表單允許的上限。";
        break;
      case UPLOAD_ERR_NO_FILE:
        $msg = "❌ 沒有選擇檔案。";
        break;
      case UPLOAD_ERR_PARTIAL:
        $msg = "❌ 檔案僅部分上傳。";
        break;
      case UPLOAD_ERR_NO_TMP_DIR:
        $msg = "❌ 找不到暫存資料夾。";
        break;
      case UPLOAD_ERR_CANT_WRITE:
        $msg = "❌ 檔案寫入失敗。";
        break;
      case UPLOAD_ERR_EXTENSION:
        $msg = "❌ PHP 擴充功能導致檔案上傳停止。";
        break;
      default:
        $msg = "❌ 檔案上傳失敗，請稍後再試。";
        break;
    }
  } elseif (!is_uploaded_file($_FILES['csv']['tmp_name'])) {
    $msg_type = 'error';
    $msg = "❌ 無效的上傳檔案請求。";
  } else {
    $file_tmp_path = $_FILES['csv']['tmp_name'];
    $file_type = mime_content_type($file_tmp_path);
    $allowed_types = ['text/csv', 'application/csv', 'text/plain'];

    if (!in_array($file_type, $allowed_types)) {
      $msg_type = 'error';
      $msg = "❌ 檔案格式錯誤 (" . htmlspecialchars($file_type) . ")，僅允許上傳 CSV 檔案。";
    } else {
      // Proceed with CSV processing
      $handle = fopen($file_tmp_path, 'r');
      $count = 0;
      if ($handle !== FALSE) {
        while (($data = fgetcsv($handle)) !== false) {
          if (count($data) >= 2) {
            $name = trim($data[0]);
            $email = trim($data[1]);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
              $stmt = $pdo->prepare("INSERT INTO recipients (project_id, name, email) VALUES (?, ?, ?)");
              $stmt->execute([$project_id, $name, $email]);
              $count++;
            }
          }
        }
        fclose($handle);
        $msg_type = 'success';
        $msg = "✅ 匯入成功，共新增 $count 筆收件人。";
      } else {
        $msg_type = 'error';
        $msg = "❌ 無法開啟上傳的 CSV 檔案。";
      }
    }
  }
}

// 顯示名單（限制使用者範圍）
if ($user_role === 'admin') {
  $stmt = $pdo->query("
    SELECT r.*, p.name AS project_name 
    FROM recipients r 
    JOIN projects p ON r.project_id = p.id 
    ORDER BY r.id DESC
  ");
} else {
  $stmt = $pdo->prepare("
    SELECT r.*, p.name AS project_name 
    FROM recipients r 
    JOIN projects p ON r.project_id = p.id 
    WHERE p.user_id = ? 
    ORDER BY r.id DESC
  ");
  $stmt->execute([$user_id]);
}
$recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>收件人名單上傳</title>
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
    label { display: block; margin: 15px 0 5px; }
    input, select {
      width: 400px;
      padding: 6px;
    }
    .btn {
      margin-top: 10px;
      padding: 8px 20px;
      background-color: #28a745;
      color: white;
      border: none;
      border-radius: 4px;
    }
    .msg {
      /* color: green; // Base color removed, will be set by .success or .error */
      font-weight: bold;
      margin-top: 10px;
      padding: 10px;
      border-radius: 4px;
      border: 1px solid transparent;
    }
    .msg.success {
      color: #155724;
      background-color: #d4edda;
      border-color: #c3e6cb;
    }
    .msg.error {
      color: #721c24;
      background-color: #f8d7da;
      border-color: #f5c6cb;
    }
    table {
      margin-top: 30px;
      width: 100%;
      border-collapse: collapse;
      background: white;
    }
    th, td {
      padding: 8px;
      border: 1px solid #ccc;
    }
    th {
      background-color: #e9ecef;
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
  <h2>📂 上傳收件人名單</h2>

  <?php if ($msg): ?><div class="msg <?= htmlspecialchars($msg_type) ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <label>選擇專案：</label>
    <select name="project_id" required>
      <option value="">-- 請選擇 --</option>
      <?php foreach ($projects as $p): ?>
        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>選擇 CSV 檔案（name,email）：</label>
    <input type="file" name="csv" accept=".csv" required>
    <?php csrf_input_field(); ?>
    <button type="submit" class="btn">上傳名單</button>
  </form>

  <?php if (count($recipients) > 0): ?>
    <h3>📋 收件人列表</h3>
    <table>
      <thead>
        <tr>
          <th>專案</th>
          <th>姓名</th>
          <th>Email</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recipients as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['project_name']) ?></td>
            <td><?= htmlspecialchars($r['name']) ?></td>
            <td><?= htmlspecialchars($r['email']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

</body>
</html>

