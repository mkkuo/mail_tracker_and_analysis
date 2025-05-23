<?php
session_start();
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>使用者登入 - MailPanel</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: linear-gradient(to right, #007bff, #00c6ff);
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .login-box {
      background: white;
      padding: 40px;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.2);
      width: 360px;
      text-align: center;
    }
    .login-box h2 {
      margin-bottom: 20px;
      color: #007bff;
    }
    .login-box input[type="email"],
    .login-box input[type="password"] {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 14px;
    }
    .login-box button {
      width: 100%;
      background-color: #007bff;
      color: white;
      border: none;
      padding: 12px;
      font-size: 16px;
      border-radius: 4px;
      margin-top: 10px;
      cursor: pointer;
    }
    .login-box button:hover {
      background-color: #0056b3;
    }
    .error {
      color: red;
      font-weight: bold;
      margin-top: 10px;
    }
  </style>
</head>
<body>

<div class="login-box">
  <h2>📬 MailPanel 登入</h2>
  <form method="post" action="check_login.php">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="密碼" required>
    <button type="submit">登入</button>
    <?php if ($error): ?>
      <div class="error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
  </form>
</div>

</body>
</html>

