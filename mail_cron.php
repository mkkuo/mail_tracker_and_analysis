<?php
require 'dbconnect.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('Asia/Taipei');

echo "🔄 Checking pending mails...\n";

// 撈取需要處理的排程
$stmt = $pdo->query("
  SELECT q.*, 
         r.email AS recipient_email, 
         p.user_id, 
         t.subject, 
         t.content,
         r.id AS recipient_id,
         p.id AS project_id
  FROM mail_queue q
  JOIN recipients r ON q.recipient_id = r.id
  JOIN projects p ON q.project_id = p.id
  JOIN templates t ON q.template_id = t.id
  WHERE q.status = 'pending' AND q.retry_count < 3 AND q.scheduled_at <= NOW()
  ORDER BY q.scheduled_at ASC
");
$queues = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 其他程式不變...

foreach ($queues as $q) {
  // ... 前段準備變數與信件內容

  try {
    // ... 設定 SMTP 與發信（同原本）
    $mail->send();
    $status = 'sent';
    $error = null;
    echo "✅ SENT\n";

    $stmt = $pdo->prepare("UPDATE mail_queue SET status = ?, sent_at = NOW(), error = ? WHERE id = ?");
    $stmt->execute([$status, $error, $q['id']]);
  } catch (Exception $e) {
    $status = 'pending';
    $error = $mail->ErrorInfo;

    $stmt = $pdo->prepare("
      UPDATE mail_queue 
      SET retry_count = retry_count + 1, 
          error = ?
      WHERE id = ?
    ");
    $stmt->execute([$error, $q['id']]);

    // 若已重試 2 次，這是第 3 次，改為 failed
    if ($q['retry_count'] + 1 >= 3) {
      $stmt = $pdo->prepare("UPDATE mail_queue SET status = 'failed' WHERE id = ?");
      $stmt->execute([$q['id']]);
      echo "❌ FAILED (3 attempts)\n";
    } else {
      echo "🔁 RETRYING (attempt " . ($q['retry_count'] + 1) . ")\n";
    }
  }
}

if (count($queues) === 0) {
  echo "✅ No pending mails.\n";
  exit;
}

// 撈取 SMTP 設定
$mail_settings = [];
$stmt = $pdo->query("
    SELECT ms.user_id, ms.type,
           sa.smtp_host, sa.smtp_port, sa.smtp_user, sa.smtp_pass,
           sa.sender_name, sa.sender_email, sa.use_tls
    FROM mail_settings ms
    LEFT JOIN smtp_accounts sa
      ON ms.user_id = sa.user_id AND ms.type = sa.provider
");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
  $mail_settings[$row['user_id']] = $row;
}

foreach ($queues as $q) {
  $uid = $q['user_id'];
  $qid = $q['id'];
  $to = $q['recipient_email'];
  $subject = $q['subject'];
  $body = $q['content'];
  $pid = $q['project_id'];
  $rid = $q['recipient_id'];

  echo "📧 Sending to: $to... ";

  if (!isset($mail_settings[$uid])) {
    $error = 'No mail settings found.';
    $status = 'failed';
    echo "❌ $error\n";
  } else {
    $s = $mail_settings[$uid];



    // 1. 替換所有 <a href="..."> 為 redirect.php 包裹
$has_link = false;
$body = preg_replace_callback(
  '/<a\s+[^>]*href=["\']([^"\']+)["\']/i',
  function ($matches) use ($pid, $uid, $rid, &$has_link) {
      $has_link = true;
      $original_url = $matches[1];
      $encoded_url = urlencode($original_url);
      $new_url = "redirect.php?pid={$pid}&uid={$uid}&rid={$rid}&url={$encoded_url}";
      return str_replace($original_url, $new_url, $matches[0]);
  },
  $body
);

// 2. 若完全沒有連結，自動加上一個
if (!$has_link) {
    $default_url = urlencode("https://mat.onsky.com.tw/?ref=mailtest");
    $fallback_link = '<p><a href="redirect.php?pid=' . $pid . '&uid=' . $uid . '&rid=' . $rid . '&url=' . $default_url . '">點我查看說明</a></p>';
    if (stripos($body, '</body>') !== false) {
        $body = str_ireplace('</body>', $fallback_link . '</body>', $body);
    } else {
        $body .= $fallback_link;
    }
}

// 🧩 建立追蹤碼 img tag
$tracking_img = '<img src="track_open.php?pid=' . $pid .
                '&uid=' . $uid .
                '&rid=' . $rid .
                '" width="1" height="1" style="display:none;">';

// 優先替換 {{tracking_code}} → fallback 插入結尾
if (stripos($body, '{{tracking_code}}') !== false) {
  $body = str_ireplace('{{tracking_code}}', $tracking_img, $body);
} elseif (stripos($body, '</body>') !== false) {
  $body = str_ireplace('</body>', $tracking_img . '</body>', $body);
} else {
  $body .= $tracking_img;
}

    $mail = new PHPMailer(true);
    try {
      $mail->isSMTP();
      $mail->Host = $s['smtp_host'];
      $mail->SMTPAuth = true;
      $mail->Username = $s['smtp_user'];
      $mail->Password = $s['smtp_pass'];
      $mail->SMTPSecure = $s['use_tls'] ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
      $mail->Port = $s['smtp_port'];

      $mail->setFrom($s['sender_email'], $s['sender_name']);
      $mail->addAddress($to);
      $mail->isHTML(true);
      $mail->Subject = $subject;
      $mail->Body = $body;

      $mail->send();
      $status = 'sent';
      $error = null;
      echo "✅ SENT\n";
    } catch (Exception $e) {
      $status = 'failed';
      $error = $mail->ErrorInfo;
      echo "❌ ERROR: $error\n";
    }
  }

  // 更新結果
  $stmt = $pdo->prepare("UPDATE mail_queue SET status=?, sent_at=NOW(), error=? WHERE id=?");
  $stmt->execute([$status, $error, $qid]);
}

