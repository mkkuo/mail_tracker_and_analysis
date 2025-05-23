<?php
// insert_project.php
require 'auth.php';
require 'dbconnect.php';

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
