<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireLogin();
$pdo       = getDB();
$note_id   = (int)($_POST['note_id'] ?? 0);
$body      = sanitize($_POST['body'] ?? '');
$parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

if (!$note_id || !$body) jsonResponse(['success' => false]);

$stmt = $pdo->prepare("
    INSERT INTO comments (note_id, user_id, parent_id, body)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$note_id, $_SESSION['user_id'], $parent_id, $body]);

$name = sanitize($_SESSION['name']);
$time = 'just now';
$html = "
<div class='comment'>
    <div class='comment-header'>
        <span class='comment-author'>{$name}</span>
        <span class='comment-time'>{$time}</span>
    </div>
    <div class='comment-body'>{$body}</div>
</div>";

jsonResponse(['success' => true, 'html' => $html]);