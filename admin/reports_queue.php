<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireAdmin();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF($_POST['csrf_token'] ?? '');
    $action  = $_POST['action'] ?? '';
    $note_id = (int)($_POST['note_id'] ?? 0);

    if ($action === 'dismiss') {
        $pdo->prepare("UPDATE reports SET status='resolved' WHERE note_id=?")
            ->execute([$note_id]);
    } elseif ($action === 'delete_note') {
        $pdo->prepare("DELETE FROM notes WHERE id=?")->execute([$note_id]);
        $pdo->prepare("UPDATE reports SET status='resolved' WHERE note_id=?")
            ->execute([$note_id]);
    }
    redirect('/notes-platform/admin/reports_queue.php');
}

$reports = $pdo->query("
    SELECT n.id as note_id, n.title, u.name as uploader,
           COUNT(r.id) as report_count,
           GROUP_CONCAT(r.reason SEPARATOR ' | ') as reasons
    FROM reports r
    JOIN notes n ON r.note_id = n.id
    JOIN users u ON n.user_id = u.id
    WHERE r.status = 'open'
    GROUP BY n.id
    ORDER BY report_count DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports Queue — NotesHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <a href="../index.php" class="nav-brand">📚 NotesHub</a>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="approve_notes.php">Approve Notes</a>
            <a href="reports_queue.php" class="active">Reports</a>
            <a href="manage_users.php">Users</a>
            <a href="manage_subjects.php">Subjects</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="admin-container">
    <div class="admin-header">
        <h1>🚩 Reports Queue</h1>
        <p><?= count($reports) ?> notes reported</p>
    </div>

    <?php if (empty($reports)): ?>
        <div class="empty-state">
            <div class="empty-icon">✅</div>
            <h3>No reports!</h3>
            <p>All content is clean.</p>
        </div>
    <?php else: ?>
        <?php foreach ($reports as $r): ?>
        <div class="approve-card">
            <div class="approve-info">
                <h3><?= sanitize($r['title']) ?></h3>
                <p class="approve-uploader">
                    By <strong><?= sanitize($r['uploader']) ?></strong>
                    · <span style="color:#D85A30;font-weight:600">
                        <?= $r['report_count'] ?> reports
                      </span>
                </p>
                <p style="font-size:0.85rem;color:#888;margin-top:0.5rem">
                    Reasons: <?= sanitize($r['reasons']) ?>
                </p>
            </div>
            <div class="approve-actions">
                <a href="../notes/view.php?id=<?= $r['note_id'] ?>"
                   class="btn" target="_blank">👁 View</a>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
                    <input type="hidden" name="note_id" value="<?= $r['note_id'] ?>">
                    <input type="hidden" name="action" value="dismiss">
                    <button type="submit" class="btn btn-success">✓ Dismiss</button>
                </form>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
                    <input type="hidden" name="note_id" value="<?= $r['note_id'] ?>">
                    <input type="hidden" name="action" value="delete_note">
                    <button type="submit" class="btn btn-danger"
                            onclick="return confirm('Delete this note permanently?')">
                        🗑 Delete Note
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>