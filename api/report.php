<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireLogin();
$pdo     = getDB();
$note_id = (int)($_POST['note_id'] ?? 0);
$reason  = sanitize($_POST['reason'] ?? 'Inappropriate content');

if (!$note_id) jsonResponse(['success' => false]);

// Check if already reported by this user
$check = $pdo->prepare("SELECT id FROM reports WHERE note_id=? AND reporter_id=?");
$check->execute([$note_id, $_SESSION['user_id']]);
if ($check->fetch()) {
    jsonResponse(['success' => false, 'message' => 'Already reported']);
}

$pdo->prepare("INSERT INTO reports (note_id, reporter_id, reason) VALUES (?,?,?)")
    ->execute([$note_id, $_SESSION['user_id'], $reason]);

// Auto-hide if 5+ reports
$count = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE note_id=? AND status='open'");
$count->execute([$note_id]);
if ($count->fetchColumn() >= 5) {
    $pdo->prepare("UPDATE notes SET status='pending' WHERE id=?")->execute([$note_id]);
}

jsonResponse(['success' => true]);