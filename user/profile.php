<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireLogin();
$pdo = getDB();

$user_id = (int)($_GET['id'] ?? $_SESSION['user_id']);

$user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$user_id]);
$user = $user->fetch();

if (!$user) redirect('/notes-platform/index.php');

$notes = $pdo->prepare("
    SELECT n.*, s.name as subject_name
    FROM notes n
    JOIN subjects s ON n.subject_id = s.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
");
$notes->execute([$user_id]);
$notes = $notes->fetchAll();

$total_downloads = array_sum(array_column($notes, 'download_count'));
$approved = array_filter($notes, fn($n) => $n['status'] === 'approved');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($user['name']) ?> — NotesHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="../index.php" class="nav-brand">📚 NotesHub</a>
        <div class="nav-links">
            <a href="../index.php">Browse</a>
            <a href="../notes/upload.php" class="btn btn-primary">+ Upload</a>
            <?php if (isAdmin()): ?>
                <a href="../admin/dashboard.php">Admin</a>
            <?php endif; ?>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="profile-container">

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar">
            <?= strtoupper(substr($user['name'], 0, 1)) ?>
        </div>
        <div class="profile-info">
            <h1><?= sanitize($user['name']) ?></h1>
            <p class="profile-email"><?= sanitize($user['email']) ?></p>
            <?php if ($user['bio']): ?>
                <p class="profile-bio"><?= sanitize($user['bio']) ?></p>
            <?php endif; ?>
            <div class="profile-stats">
                <div class="profile-stat">
                    <span class="stat-num"><?= count($approved) ?></span>
                    <span class="stat-lbl">Notes</span>
                </div>
                <div class="profile-stat">
                    <span class="stat-num"><?= $total_downloads ?></span>
                    <span class="stat-lbl">Downloads</span>
                </div>
                <div class="profile-stat">
                    <span class="stat-num"><?= $user['reputation'] ?></span>
                    <span class="stat-lbl">Reputation</span>
                </div>
            </div>
        </div>
        <?php if ($_SESSION['user_id'] == $user_id): ?>
        <div class="profile-nav">
            <a href="my_notes.php" class="btn">My Notes</a>
            <a href="bookmarks.php" class="btn">Bookmarks</a>
            <a href="history.php" class="btn">History</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Notes List -->
    <div class="profile-notes">
        <h2>Uploaded Notes</h2>
        <?php if (empty($notes)): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3>No notes yet</h3>
                <p>Upload your first note!</p>
                <a href="../notes/upload.php" class="btn btn-primary">Upload Note</a>
            </div>
        <?php else: ?>
            <div class="notes-grid">
                <?php foreach ($notes as $note): ?>
                <div class="note-card">
                    <div class="note-thumb">
                        <div class="thumb-placeholder">
                            <?= strtoupper(pathinfo($note['file_path'], PATHINFO_EXTENSION)) ?>
                        </div>
                    </div>
                    <div class="note-info">
                        <div class="note-meta">
                            <span class="note-subject"><?= sanitize($note['subject_name']) ?></span>
                            <span class="status-badge status-<?= $note['status'] ?>">
                                <?= ucfirst($note['status']) ?>
                            </span>
                        </div>
                        <h3 class="note-title">
                            <a href="../notes/view.php?id=<?= $note['id'] ?>">
                                <?= sanitize($note['title']) ?>
                            </a>
                        </h3>
                        <div class="note-footer">
                            <span class="note-stars"><?= renderStars($note['avg_rating']) ?></span>
                            <span class="note-downloads">⬇ <?= $note['download_count'] ?></span>
                            <span><?= timeAgo($note['created_at']) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>