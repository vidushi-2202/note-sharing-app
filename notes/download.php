<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireLogin();
$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

if (!$id) redirect('/notes-platform/index.php');

$stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ? AND status = 'approved'");
$stmt->execute([$id]);
$note = $stmt->fetch();

if (!$note) redirect('/notes-platform/index.php');

// Log download
$pdo->prepare("INSERT INTO downloads (note_id, user_id) VALUES (?, ?)")
    ->execute([$id, $_SESSION['user_id']]);

// Increment counter
$pdo->prepare("UPDATE notes SET download_count = download_count + 1 WHERE id = ?")
    ->execute([$id]);

// Serve file
$filePath = __DIR__ . '/../uploads/notes/' . $note['file_path'];

if (!file_exists($filePath)) {
    die('File not found.');
}

$ext          = pathinfo($note['file_path'], PATHINFO_EXTENSION);
$originalName = sanitize($note['title']) . '.' . $ext;

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $originalName . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;