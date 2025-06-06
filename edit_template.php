<?php
require_once 'auth.php';
require_once 'csrf_guard.php';
require_once 'dbconnect.php';

$template_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

if (!$template_id) {
    die("❌ 未指定範本 ID。");
}

// 讀取範本資料
$stmt = $pdo->prepare("SELECT t.*, p.name AS project_name, p.user_id AS project_owner
                       FROM templates t
                       JOIN projects p ON t.project_id = p.id
                       WHERE t.id = ?");
$stmt->execute([$template_id]);
$template = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$template) {
    die("❌ 找不到該範本。");
}
if ($user_role !== 'admin' && $template['project_owner'] != $user_id) {
    die("❌ 無權編輯此範本。");
}

// 撈可選擇的專案清單
if ($user_role === 'admin') {
    $projects = $pdo->query("SELECT id, name FROM projects ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT id, name FROM projects WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>編輯郵件範本</title>
  <!--<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>-->
   <script src="https://cdn.ckeditor.com/4.25.1-lts/standard/ckeditor.js"></script>
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
    label { display: block; margin-top: 15px; margin-bottom: 5px; }
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
  <h2>📝 編輯郵件範本</h2>

  <form method="post" action="update_template.php">
    <input type="hidden" name="id" value="<?= $template['id'] ?>">

    <label>選擇專案：</label>
    <select name="project_id" required>
      <option value="">-- 請選擇 --</option>
      <?php foreach ($projects as $proj): ?>
        <option value="<?= $proj['id'] ?>" <?= $proj['id'] == $template['project_id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($proj['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label>信件標題：</label>
    <input type="text" name="subject" value="<?= htmlspecialchars($template['subject']) ?>" required>

    <label>信件內容：</label>
    <textarea name="content" id="editor"><?= htmlspecialchars($template['content']) ?></textarea>
    <?php csrf_input_field(); ?>
    <button type="submit" class="btn">儲存變更</button>
  </form>
</div>

<script>
  CKEDITOR.replace('editor');
</script>
</body>
</html>

