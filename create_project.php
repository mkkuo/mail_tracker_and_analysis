<?php
require 'auth.php';
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>建立新專案</title>
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
    label { display: block; margin: 15px 0 5px; font-weight: bold; }
    input, textarea {
      width: 400px; padding: 8px; font-size: 14px;
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
  <h2>🆕 建立新專案</h2>

  <form method="post" action="insert_project.php">
    <label>專案名稱：</label>
    <input type="text" name="name" required>

    <label>專案描述：</label>
    <textarea name="description" rows="5"></textarea>

    <button type="submit" class="btn">建立專案</button>
  </form>
</div>

</body>
</html>

