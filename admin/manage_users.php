<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireAdmin();
$pdo = getDB();

// Handle ban/unban
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF($_POST['csrf_token'] ?? '');
    $user_id = (int)($_POST['user_id'] ?? 0);
    $action  = $_POST['action'] ?? '';

    if ($action === 'ban') {
        $pdo->prepare("UPDATE users SET is_banned=1 WHERE id=? AND role='student'")
            ->execute([$user_id]);
    } elseif ($action === 'unban') {
        $pdo->prepare("UPDATE users SET is_banned=0 WHERE id=?")
            ->execute([$user_id]);
    }
    redirect('/notes-platform/admin/manage_users.php');
}

$users = $pdo->query("
    SELECT u.*,
           COUNT(n.id) as note_count,
           SUM(n.download_count) as total_downloads
    FROM users u
    LEFT JOIN notes n ON u.id = n.user_id AND n.status = 'approved'
    WHERE u.role = 'student'
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users — NotesHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <a href="../index.php" class="nav-brand">📚 NotesHub</a>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="approve_notes.php">Approve Notes</a>
            <a href="reports_queue.php">Reports</a>
            <a href="manage_users.php" class="active">Users</a>
            <a href="manage_subjects.php">Subjects</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="admin-container">
    <div class="admin-header">
        <h1>👥 Manage Users</h1>
        <p><?= count($users) ?> students registered</p>
    </div>

    <div class="admin-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Notes</th>
                    <th>Downloads</th>
                    <th>Reputation</th>
                    <th>Joined</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= sanitize($u['name']) ?></td>
                    <td><?= sanitize($u['email']) ?></td>
                    <td><?= $u['note_count'] ?></td>
                    <td><?= number_format($u['total_downloads'] ?? 0) ?></td>
                    <td><?= $u['reputation'] ?></td>
                    <td><?= timeAgo($u['created_at']) ?></td>
                    <td>
                        <span class="status-badge <?= $u['is_banned'] ? 'status-rejected' : 'status-approved' ?>">
                            <?= $u['is_banned'] ? 'Banned' : 'Active' ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="action" value="<?= $u['is_banned'] ? 'unban' : 'ban' ?>">
                            <button type="submit" class="btn <?= $u['is_banned'] ? 'btn-success' : 'btn-danger' ?>"
                                    style="font-size:0.8rem;padding:4px 10px"
                                    onclick="return confirm('Are you sure?')">
                                <?= $u['is_banned'] ? 'Unban' : 'Ban' ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>