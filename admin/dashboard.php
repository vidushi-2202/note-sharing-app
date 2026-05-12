<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireAdmin();
$pdo = getDB();

$total_notes     = $pdo->query("SELECT COUNT(*) FROM notes WHERE status='approved'")->fetchColumn();
$pending_notes   = $pdo->query("SELECT COUNT(*) FROM notes WHERE status='pending'")->fetchColumn();
$total_users     = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$total_downloads = $pdo->query("SELECT SUM(download_count) FROM notes")->fetchColumn() ?? 0;

$recent_notes = $pdo->query("
    SELECT n.*, u.name as uploader, s.name as subject_name
    FROM notes n
    JOIN users u ON n.user_id = u.id
    JOIN subjects s ON n.subject_id = s.id
    ORDER BY n.created_at DESC LIMIT 5
")->fetchAll();

$top_uploaders = $pdo->query("
    SELECT u.name, COUNT(n.id) as note_count, SUM(n.download_count) as total_downloads
    FROM users u
    LEFT JOIN notes n ON u.id = n.user_id AND n.status = 'approved'
    WHERE u.role = 'student'
    GROUP BY u.id
    ORDER BY note_count DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — NotesHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="../index.php" class="nav-brand">📚 NotesHub</a>
        <div class="nav-links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="approve_notes.php">Approve Notes</a>
            <a href="reports_queue.php">Reports</a>
            <a href="manage_users.php">Users</a>
            <a href="manage_subjects.php">Subjects</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="admin-container">
    <div class="admin-header">
        <h1>Admin Dashboard</h1>
        <p>Welcome back, <?= sanitize($_SESSION['name']) ?>!</p>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📄</div>
            <div class="stat-value"><?= $total_notes ?></div>
            <div class="stat-label">Approved Notes</div>
        </div>
        <div class="stat-card stat-warning">
            <div class="stat-icon">⏳</div>
            <div class="stat-value"><?= $pending_notes ?></div>
            <div class="stat-label">Pending Approval</div>
            <?php if ($pending_notes > 0): ?>
                <a href="approve_notes.php" class="stat-action">Review now →</a>
            <?php endif; ?>
        </div>
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-value"><?= $total_users ?></div>
            <div class="stat-label">Students</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⬇</div>
            <div class="stat-value"><?= number_format($total_downloads) ?></div>
            <div class="stat-label">Total Downloads</div>
        </div>
    </div>

    <div class="admin-grid">
        <!-- Recent Notes -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2>Recent Uploads</h2>
                <a href="approve_notes.php">View all →</a>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Uploader</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_notes as $note): ?>
                    <tr>
                        <td><?= sanitize(substr($note['title'], 0, 30)) ?>...</td>
                        <td><?= sanitize($note['uploader']) ?></td>
                        <td><?= sanitize($note['subject_name']) ?></td>
                        <td>
                            <span class="status-badge status-<?= $note['status'] ?>">
                                <?= ucfirst($note['status']) ?>
                            </span>
                        </td>
                        <td><?= timeAgo($note['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Top Uploaders -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2>Top Uploaders</h2>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Notes</th>
                        <th>Downloads</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_uploaders as $u): ?>
                    <tr>
                        <td><?= sanitize($u['name']) ?></td>
                        <td><?= $u['note_count'] ?></td>
                        <td><?= number_format($u['total_downloads'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>