<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireLogin();
$pdo = getDB();

$history = $pdo->prepare("
    SELECT d.downloaded_at, n.id, n.title, n.file_path,
           s.name as subject_name, u.name as uploader
    FROM downloads d
    JOIN notes n ON d.note_id = n.id
    JOIN subjects s ON n.subject_id = s.id
    JOIN users u ON n.user_id = u.id
    WHERE d.user_id = ?
    ORDER BY d.downloaded_at DESC
    LIMIT 50
");
$history->execute([$_SESSION['user_id']]);
$history = $history->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download History — NotesHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <a href="../index.php" class="nav-brand">📚 NotesHub</a>
        <div class="nav-links">
            <a href="../index.php">Browse</a>
            <a href="profile.php">Profile</a>
            <a href="bookmarks.php">Bookmarks</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="admin-container">
    <div class="admin-header">
        <h1>⬇ Download History</h1>
        <p><?= count($history) ?> downloads</p>
    </div>

    <?php if (empty($history)): ?>
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <h3>No downloads yet</h3>
            <p>Notes you download will appear here.</p>
            <a href="../index.php" class="btn btn-primary">Browse Notes</a>
        </div>
    <?php else: ?>
        <div class="admin-card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Note</th>
                        <th>Subject</th>
                        <th>Uploader</th>
                        <th>Downloaded</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                    <tr>
                        <td><?= sanitize($h['title']) ?></td>
                        <td><?= sanitize($h['subject_name']) ?></td>
                        <td><?= sanitize($h['uploader']) ?></td>
                        <td><?= timeAgo($h['downloaded_at']) ?></td>
                        <td>
                            <a href="../notes/view.php?id=<?= $h['id'] ?>"
                               class="btn" style="font-size:0.8rem;padding:4px 10px">
                               View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>