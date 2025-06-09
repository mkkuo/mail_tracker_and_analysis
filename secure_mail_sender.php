<?php
require 'dbconnect.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 基於環境變數的寄件設定
$SMTP_HOST = getenv('SMTP_HOST');
$SMTP_PORT = getenv('SMTP_PORT') ?: 587;
$SMTP_USER = getenv('SMTP_USER');
$SMTP_PASS = getenv('SMTP_PASS');
$SMTP_SECURE = getenv('SMTP_SECURE') ?: PHPMailer::ENCRYPTION_STARTTLS;
$SENDER_NAME = getenv('SENDER_NAME') ?: 'Mailer';
$SENDER_EMAIL = getenv('SENDER_EMAIL');

if (!$SMTP_HOST || !$SMTP_USER || !$SMTP_PASS || !$SENDER_EMAIL) {
    echo "Missing SMTP configuration in environment variables." . PHP_EOL;
    exit(1);
}

function log_msg($msg)
{
    $line = '[' . date('c') . "] $msg\n";
    file_put_contents('secure_mail_sender.log', $line, FILE_APPEND);
    echo $line;
}

log_msg('Checking pending mails...');

$stmt = $pdo->query("SELECT q.*, r.email AS recipient_email, t.subject, t.content FROM mail_queue q JOIN recipients r ON q.recipient_id = r.id JOIN templates t ON q.template_id = t.id WHERE q.status = 'pending' ORDER BY q.scheduled_at ASC");
$queues = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$queues) {
    log_msg('No pending mails.');
    exit(0);
}

foreach ($queues as $q) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = $SMTP_USER;
        $mail->Password = $SMTP_PASS;
        $mail->SMTPSecure = $SMTP_SECURE;
        $mail->Port = $SMTP_PORT;
        $mail->setFrom($SENDER_EMAIL, $SENDER_NAME);
        $mail->addAddress($q['recipient_email']);
        $mail->isHTML(true);
        $mail->Subject = $q['subject'];
        $mail->Body = $q['content'];
        $mail->send();
        $status = 'sent';
        $error = null;
        log_msg('SENT to ' . $q['recipient_email']);
    } catch (Exception $e) {
        $status = 'failed';
        $error = $mail->ErrorInfo;
        log_msg('FAILED to ' . $q['recipient_email'] . ': ' . $error);
    }
    $stmtUp = $pdo->prepare('UPDATE mail_queue SET status=?, sent_at=NOW(), error=? WHERE id=?');
    $stmtUp->execute([$status, $error, $q['id']]);