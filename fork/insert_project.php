<?php
// insert_project.php
require_once 'auth.php';
require_once 'csrf_guard.php'; // Needs to be after auth.php if auth.php starts session
verify_csrf_or_die(); // Call this early
require_once 'dbconnect.php';

$name = $_POST['name'] ?? '';
$desc = $_POST['description'] ?? '';
$user_id = $_SESSION['user_id'];

if (trim($name) === '') {
  die('Project name is required.');
}

$stmt = $pdo->prepare("INSERT INTO projects (user_id, name, description) VALUES (?, ?, ?)");
$stmt->execute([$user_id, $name, $desc]);

header("Location: dashboard.php");
exit;
?>
