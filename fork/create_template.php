<?php
require_once 'auth.php';
require_once 'csrf_guard.php';
require_once 'dbconnect.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// 撈取使用者的專案清單
if ($user_role === 'admin') {
    $stmt = $pdo->query("SELECT id, name FROM projects ORDER BY created_at DESC");
} else {
    $stmt = $pdo->prepare("SELECT id, name FROM projects WHERE user_id = ?");
    $stmt->execute([$user_id]);
}
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>建立郵件範本</title>
  <script src="https://cdn.ckeditor.com/4.25.1-lts/standard/ckeditor.js"></script>
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
    label {
      display: block;
      margin-top: 15px;
      margin-bottom: 5px;
    }
    input[type="text"], select {
      padding: 6px;
      width: 400px;
    }
    textarea {
      width: 100%;
      height: 300px;
    }
    .btn {
      margin-top: 20px;
      padding: 10px 20px;
      background-color: #28a745;
      color: white;
      border: none;
      border-radius: 4px;
    }
  </style>
</head>
<body>

<div class="sidebar">
  <h3>📬 MailPanel</h3>
  <a href="dashboard.php">🏠 專案總覽</a>
  <a href="create_project.php">➕ 建立專案</a>
  <a href="templates.php">✉️ 郵件範本</a>
  <a href="recipients.php">📂 寄送名單上傳</a>
  <a href="mail_queue.php">📤 寄送排程</a>
  <a href="report.php">📊 測試成果報告</a>
  <a href="log.php">📑 行為紀錄</a>
  <a href="settings.php">⚙️ 寄信設定</a>
  <a href="logout.php">🚪 登出</a>
</div>

<div class="main">
  <h2>✉️ 建立新郵件範本</h2>

  <form action="insert_template.php" method="post">
    <label>選擇專案：</label>
    <select name="project_id" required>
      <option value="">-- 請選擇 --</option>
      <?php foreach ($projects as $proj): ?>
        <option value="<?= $proj['id'] ?>"><?= htmlspecialchars($proj['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>信件標題：</label>
    <input type="text" name="subject" required>

    <label>信件內容：</label>   {{tracking_code}} 
    <textarea name="content" id="editor"></textarea>
    <?php csrf_input_field(); ?>
    <button type="submit" class="btn">儲存範本</button>
  </form>
</div>

<script>
  CKEDITOR.replace('editor');
</script>

</body>
</html>

