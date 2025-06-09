<?php
require 'dbconnect.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('Asia/Taipei'); // Set timezone for date functions

echo "🔄 Checking pending mails...\n";

// Fetch pending email queues that are due, not exceeding retry limits
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

// The following loop structure appears to be duplicated or misplaced in the original code.
// The primary mail sending loop is further down. This section seems to be an incomplete
// or vestigial part of a retry mechanism. For clarity, I will comment it out
// as the main logic is handled later. If this was intended for a specific pre-check
// or different retry logic, it would need further clarification.
/*
foreach ($queues as $q) {
  // ... 前段準備變數與信件內容

  try {
    // ... 設定 SMTP 與發信（同原本）
    // $mail->send(); // This would require $mail to be initialized per user settings
    $status = 'sent';
    $error = null;
    echo "✅ SENT (Placeholder - actual send below)\n";

    $stmt = $pdo->prepare("UPDATE mail_queue SET status = ?, sent_at = NOW(), error = ? WHERE id = ?");
    $stmt->execute([$status, $error, $q['id']]);
  } catch (Exception $e) {
    $status = 'pending';
    // $error = $mail->ErrorInfo; // $mail not defined here in this scope

    $stmt = $pdo->prepare("
      UPDATE mail_queue 
      SET retry_count = retry_count + 1, 
          error = ?
      WHERE id = ?
    ");
    // $stmt->execute([$error, $q['id']]); // $error might not be defined correctly

    // 若已重試 2 次，這是第 3 次，改為 failed
    if ($q['retry_count'] + 1 >= 3) {
      $stmt = $pdo->prepare("UPDATE mail_queue SET status = 'failed' WHERE id = ?");
      $stmt->execute([$q['id']]);
      echo "❌ FAILED (3 attempts - Placeholder)\n";
    } else {
      echo "🔁 RETRYING (attempt " . ($q['retry_count'] + 1) . " - Placeholder)\n";
    }
  }
}
*/

if (count($queues) === 0) {
  echo "✅ No pending mails.\n";
  exit;
}

// Fetch all mail settings for users to avoid multiple queries inside the loop
$mail_settings = [];
$stmt_settings = $pdo->query("SELECT * FROM mail_settings");
foreach ($stmt_settings->fetchAll(PDO::FETCH_ASSOC) as $row) {
  $mail_settings[$row['user_id']] = $row;
}

// Main loop for processing each queued email
foreach ($queues as $q) {
  $uid = $q['user_id']; // User ID owning the project/settings
  $qid = $q['id'];      // Queue ID
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
    $s = $mail_settings[$uid]; // SMTP settings for the user

    // --- Start: Email Body Processing for Tracking ---

    // 1. Wrap all <a href="..."> links with redirect.php for click tracking
    $has_link_in_template = false; // Flag to check if template originally had links
    $body_processed_links = preg_replace_callback(
      '/<a\s+[^>]*href=["\']([^"\']+)["\']/i', // Regex to find <a href="...">
      function ($matches) use ($pid, $uid, $rid, &$has_link_in_template) {
          $has_link_in_template = true;
          $original_url = $matches[1];
          // Avoid re-wrapping if already a redirect link (basic check)
          if (strpos($original_url, 'redirect.php?') === 0) {
              return $matches[0]; // Return original if already wrapped
          }
          $encoded_url = urlencode($original_url);
          $new_url = "redirect.php?pid={$pid}&uid={$uid}&rid={$rid}&url={$encoded_url}";
          return str_replace($original_url, $new_url, $matches[0]);
      },
      $body // Original template content
    );

    // 2. If the template had no links, add a default fallback link for tracking purposes
    if (!$has_link_in_template) {
        $default_tracking_url = urlencode("https://mat.onsky.com.tw/?ref=mail_fallback_link_project_" . $pid);
        $fallback_link_html = '<p><a href="redirect.php?pid=' . $pid . '&uid=' . $uid . '&rid=' . $rid . '&url=' . $default_tracking_url . '">.</a></p>'; // Minimal visible link
        if (stripos($body_processed_links, '</body>') !== false) {
            $body_processed_links = str_ireplace('</body>', $fallback_link_html . '</body>', $body_processed_links);
        } else {
            $body_processed_links .= $fallback_link_html;
        }
    }

    // 3. Insert open tracking pixel
    // Create the tracking pixel HTML
    $tracking_pixel_img = '<img src="track_open.php?pid=' . $pid .
                          '&uid=' . $uid .
                          '&rid=' . $rid .
                          '" width="1" height="1" alt="" style="display:none;"/>'; // Added alt=""

    // Replace placeholder or append to body
    if (stripos($body_processed_links, '{{tracking_code}}') !== false) {
      $final_body = str_ireplace('{{tracking_code}}', $tracking_pixel_img, $body_processed_links);
    } elseif (stripos($body_processed_links, '</body>') !== false) {
      $final_body = str_ireplace('</body>', $tracking_pixel_img . '</body>', $body_processed_links);
    } else {
      $final_body = $body_processed_links . $tracking_pixel_img;
    }
    // --- End: Email Body Processing for Tracking ---

    $mail = new PHPMailer(true); // Enable exceptions
    try {
      // SMTP Configuration
      $mail->isSMTP();
      $mail->Host = $s['smtp_host'];
      $mail->SMTPAuth = true;
      $mail->Username = $s['smtp_user'];
      $mail->Password = $s['smtp_pass'];
      $mail->SMTPSecure = $s['use_tls'] ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
      $mail->Port = (int)$s['smtp_port']; // Ensure port is integer
      $mail->CharSet = 'UTF-8'; // Set CharSet

      // Recipients and Content
      $mail->setFrom($s['sender_email'], $s['sender_name']);
      $mail->addAddress($to); // Recipient email
      $mail->isHTML(true);
      $mail->Subject = $subject;
      $mail->Body = $final_body; // Use the processed body

      $mail->send();
      $status = 'sent';
      $error_message = null; // Clear error on success
      echo "✅ SENT\n";

    } catch (Exception $e) {
      // Handle mail sending errors
      $status = 'pending'; // Keep as pending for retry
      $error_message = $mail->ErrorInfo; // Get specific error from PHPMailer
      echo "❌ ERROR: $error_message\n";

      // Increment retry count and update error message
      $stmt_retry = $pdo->prepare("
        UPDATE mail_queue 
        SET retry_count = retry_count + 1, 
            error = ?
        WHERE id = ?
      ");
      $stmt_retry->execute([$error_message, $qid]);
      
      // If retry attempts exhausted, mark as failed
      if ($q['retry_count'] + 1 >= 3) {
        $status = 'failed'; // Update status to failed for the final update
        $stmt_fail = $pdo->prepare("UPDATE mail_queue SET status = 'failed' WHERE id = ?");
        $stmt_fail->execute([$qid]);
        echo "❌ FAILED (3 attempts reached for queue ID: $qid)\n";
      } else {
        echo "🔁 RETRYING (attempt " . ($q['retry_count'] + 1) . " for queue ID: $qid)\n";
      }
    }
  }

  // Update queue status (sent, or failed if retries exhausted)
  // Note: if status is 'pending' due to retry, it's handled above. This is for final 'sent' or 'failed' state.
  if ($status === 'sent' || $status === 'failed') {
      $update_stmt_sql = "UPDATE mail_queue SET status = ?, error = ?";
      if ($status === 'sent') {
          $update_stmt_sql .= ", sent_at = NOW()";
      }
      $update_stmt_sql .= " WHERE id = ?";
      $stmt_update = $pdo->prepare($update_stmt_sql);
      $stmt_update->execute([$status, $error_message, $qid]);
  }
}

