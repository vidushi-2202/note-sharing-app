<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireLogin();
$pdo     = getDB();
$note_id = (int)($_POST['note_id'] ?? 0);
$stars   = (int)($_POST['stars'] ?? 0);

if (!$note_id || $stars < 1 || $stars > 5) {
    jsonResponse(['success' => false]);
}

$stmt = $pdo->prepare("
    INSERT INTO ratings (note_id, user_id, stars)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE stars = VALUES(stars)
");
$stmt->execute([$note_id, $_SESSION['user_id'], $stars]);

// Update avg_rating on notes table
$avg = $pdo->prepare("SELECT AVG(stars) FROM ratings WHERE note_id = ?");
$avg->execute([$note_id]);
$newAvg = round($avg->fetchColumn(), 2);

$pdo->prepare("UPDATE notes SET avg_rating = ? WHERE id = ?")
    ->execute([$newAvg, $note_id]);

jsonResponse(['success' => true, 'avg' => $newAvg]);