<?php
require_once '../includes/db.php';
require_once '../includes/helpers.php';

$q = sanitize($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare("
    SELECT n.id, n.title, n.avg_rating, n.download_count,
           n.file_path, s.name as subject_name, u.name as uploader
    FROM notes n
    JOIN subjects s ON n.subject_id = s.id
    JOIN users u ON n.user_id = u.id
    WHERE n.status = 'approved'
    AND (n.title LIKE ? OR n.description LIKE ? OR n.tags LIKE ? OR s.name LIKE ?)
    ORDER BY n.download_count DESC
    LIMIT 6
");
$like = "%$q%";
$stmt->execute([$like, $like, $like, $like]);
$notes = $stmt->fetchAll();

echo json_encode($notes);