<?php
require 'auth.php';
require 'dbconnect.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

$selected_project = $_GET['project_id'] ?? null;

// 專案清單
if ($user_role === 'admin') {
  $stmt = $pdo->query("SELECT id, name FROM projects ORDER BY created_at DESC");
} else {
  $stmt = $pdo->prepare("SELECT id, name FROM projects WHERE user_id = ?");
  $stmt->execute([$user_id]);
}
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 初始化統計數據
$stats = [
  'sent_total' => 0,
  'sent_users' => 0,
  'open_count' => 0,
  'open_users' => 0,
  'click_count' => 0,
  'click_users' => 0,
];

if ($selected_project) {
  // 寄送成功的總筆數 + 人數
  $stmt = $pdo->prepare("SELECT COUNT(*) AS total, COUNT(DISTINCT recipient_id) AS users FROM mail_queue WHERE status='sent' AND project_id = ?");
  $stmt->execute([$selected_project]);
  $q = $stmt->fetch();
  $stats['sent_total'] = $q['total'];
  $stats['sent_users'] = $q['users'];

  // 開信統計
  $stmt = $pdo->prepare("SELECT COUNT(*) AS total, COUNT(DISTINCT recipient_id) AS users FROM mail_open_log WHERE project_id = ?");
  $stmt->execute([$selected_project]);
  $o = $stmt->fetch();
  $stats['open_count'] = $o['total'];
  $stats['open_users'] = $o['users'];

  // 點擊統計
  $stmt = $pdo->prepare("SELECT COUNT(*) AS total, COUNT(DISTINCT recipient_id) AS users FROM mail_click_log WHERE project_id = ?");
  $stmt->execute([$selected_project]);
  $c = $stmt->fetch();
  $stats['click_count'] = $c['total'];
  $stats['click_users'] = $c['users'];
}

// 計算比率
function rate($num, $denom) {
  return $denom > 0 ? round(($num / $denom) * 100, 2) : 0;
}

$open_rate_times = rate($stats['open_count'], $stats['sent_total']);
$open_rate_users = rate($stats['open_users'], $stats['sent_users']);
$click_rate_times = rate($stats['click_count'], $stats['sent_total']);
$click_rate_users = rate($stats['click_users'], $stats['sent_users']);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>📊 成果報告</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { margin: 0; font-family: Arial, sans-serif; background: #f8f9fa; display: flex; }
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
    .section { margin-bottom: 30px; }
    table {
      width: 100%; border-collapse: collapse; background: white;
    }
    th, td {
      padding: 10px; border: 1px solid #ccc; text-align: center;
    }
    th { background-color: #e9ecef; }
    select { padding: 6px; }
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
  <h2>📊 測試成果報告</h2>

  <form method="get">
    <label>選擇專案：
      <select name="project_id" onchange="this.form.submit()">
        <option value="">-- 請選擇 --</option>
        <?php foreach ($projects as $p): ?>
          <option value="<?= $p['id'] ?>" <?= $selected_project == $p['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
  </form>

  <?php if ($selected_project): ?>
    <div class="section">
      <h3>📈 數值統計</h3>
      <table>
        <thead>
          <tr>
            <th></th>
            <th>寄信總數</th>
            <th>開信總次數</th>
            <th>開信人數</th>
            <th>點擊總次數</th>
            <th>點擊人數</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>數值</td>
            <td><?= $stats['sent_total'] ?></td>
            <td><?= $stats['open_count'] ?></td>
            <td><?= $stats['open_users'] ?></td>
            <td><?= $stats['click_count'] ?></td>
            <td><?= $stats['click_users'] ?></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="section">
      <h3>📊 比率統計（%）</h3>
      <canvas id="rateChart" height="100"></canvas>
    </div>

    <script>
    const ctx = document.getElementById('rateChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['開信人次率', '開信人數率', '點擊人次率', '點擊人數率'],
        datasets: [{
          label: '比率 (%)',
          data: [<?= $open_rate_times ?>, <?= $open_rate_users ?>, <?= $click_rate_times ?>, <?= $click_rate_users ?>],
          backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545']
        }]
      },
      options: {
        scales: {
          y: { beginAtZero: true, max: 100 }
        }
      }
    });
    </script>

  <?php elseif ($_SERVER['REQUEST_METHOD'] === 'GET'): ?>
    <p>請先選擇一個專案查看統計報告。</p>
  <?php endif; ?>
</div>
<form method="get" action="export_report.php" style="margin-top: 20px;">
  <input type="hidden" name="project_id" value="<?= $selected_project ?>">
  <button type="submit">📥 匯出 CSV 報表</button>
</form>

</body>
</html>

