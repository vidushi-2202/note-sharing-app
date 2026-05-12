<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireLogin();
$pdo = getDB();

$bookmarks = $pdo->prepare("
    SELECT n.*, s.name as subject_name, u.name as uploader
    FROM bookmarks bk
    JOIN notes n ON bk.note_id = n.id
    JOIN subjects s ON n.subject_id = s.id
    JOIN users u ON n.user_id = u.id
    WHERE bk.user_id = ?
    ORDER BY bk.saved_at DESC
");
$bookmarks->execute([$_SESSION['user_id']]);
$bookmarks = $bookmarks->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookmarks — NotesHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <a href="../index.php" class="nav-brand">📚 NotesHub</a>
        <div class="nav-links">
            <a href="../index.php">Browse</a>
            <a href="profile.php">Profile</a>
            <a href="history.php">History</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="admin-container">
    <div class="admin-header">
        <h1>❤️ My Bookmarks</h1>
        <p><?= count($bookmarks) ?> saved notes</p>
    </div>

    <?php if (empty($bookmarks)): ?>
        <div class="empty-state">
            <div class="empty-icon">🤍</div>
            <h3>No bookmarks yet</h3>
            <p>Save notes you like by clicking the Save button on any note.</p>
            <a href="../index.php" class="btn btn-primary">Browse Notes</a>
        </div>
    <?php else: ?>
        <div class="notes-grid">
            <?php foreach ($bookmarks as $note): ?>
            <div class="note-card">
                <div class="note-thumb">
                    <div class="thumb-placeholder">
                        <?= strtoupper(pathinfo($note['file_path'], PATHINFO_EXTENSION)) ?>
                    </div>
                </div>
                <div class="note-info">
                    <div class="note-meta">
                        <span class="note-subject"><?= sanitize($note['subject_name']) ?></span>
                    </div>
                    <h3 class="note-title">
                        <a href="../notes/view.php?id=<?= $note['id'] ?>">
                            <?= sanitize($note['title']) ?>
                        </a>
                    </h3>
                    <div class="note-footer">
                        <span class="note-stars"><?= renderStars($note['avg_rating']) ?></span>
                        <span class="note-downloads">⬇ <?= $note['download_count'] ?></span>
                        <span>by <?= sanitize($note['uploader']) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>