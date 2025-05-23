<?php
require 'auth.php';
require 'dbconnect.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// 取得目前使用者的專案
if ($user_role === 'admin') {
    $stmt = $pdo->query("SELECT id, name FROM projects ORDER BY created_at DESC");
} else {
    $stmt = $pdo->prepare("SELECT id, name FROM projects WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
}
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>建立寄信排程</title>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
    select, input[type="datetime-local"] {
      width: 400px;
      padding: 6px;
    }
    .multi-select {
      width: 400px;
      height: 150px;
    }
    .btn {
      margin-top: 20px;
      padding: 10px 20px;
      background-color: #007bff;
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
  <a href="schedule_mail.php">📅 建立排程</a>
  <a href="mail_queue.php">📤 寄送排程</a>
  <a href="report.php">📊 測試成果報告</a>
  <a href="log.php">📑 行為紀錄</a>
  <a href="settings.php">⚙️ 寄信設定</a>
  <a href="logout.php">🚪 登出</a>
</div>

<div class="main">
  <h2>📅 建立寄信排程</h2>

  <form method="post" action="insert_schedule.php">
    <label>選擇專案：</label>
    <select name="project_id" id="project-select" required>
      <option value="">-- 請選擇專案 --</option>
      <?php foreach ($projects as $p): ?>
        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>選擇信件範本：</label>
    <select name="template_id" id="template-select" required>
      <option value="">-- 請先選擇專案 --</option>
    </select>

    <label>選擇收件人（可複選）：</label>
    <select name="recipients[]" id="recipient-select" class="multi-select" multiple required>
      <option value="">-- 請先選擇專案 --</option>
    </select>

    <label>設定寄送時間：</label>
    <input type="datetime-local" name="scheduled_at" required
           value="<?= date('Y-m-d\TH:i', strtotime('+10 minutes')) ?>">

    <br>
    <button type="submit" class="btn">建立排程</button>
  </form>
</div>

<script>
// 專案改變時，載入範本與收件人
$('#project-select').on('change', function () {
  const projectId = $(this).val();

  if (!projectId) return;

  // 取得 templates
  $.post('api_get_templates.php', {project_id: projectId}, function (data) {
    $('#template-select').html(data);
  });

  // 取得 recipients
  $.post('api_get_recipients.php', {project_id: projectId}, function (data) {
    $('#recipient-select').html(data);
  });
});
</script>

</body>
</html>

