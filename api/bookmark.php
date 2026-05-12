<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireLogin();
$pdo     = getDB();
$note_id = (int)($_POST['note_id'] ?? 0);

if (!$note_id) jsonResponse(['success' => false]);

$check = $pdo->prepare("SELECT id FROM bookmarks WHERE note_id=? AND user_id=?");
$check->execute([$note_id, $_SESSION['user_id']]);

if ($check->fetch()) {
    $pdo->prepare("DELETE FROM bookmarks WHERE note_id=? AND user_id=?")
        ->execute([$note_id, $_SESSION['user_id']]);
    jsonResponse(['success' => true, 'bookmarked' => false]);
} else {
    $pdo->prepare("INSERT INTO bookmarks (note_id, user_id) VALUES (?,?)")
        ->execute([$note_id, $_SESSION['user_id']]);
    jsonResponse(['success' => true, 'bookmarked' => true]);
}