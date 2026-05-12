<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireAdmin();
$pdo = getDB();

// Handle approve / reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF($_POST['csrf_token'] ?? '');

    $note_id = (int)($_POST['note_id'] ?? 0);
    $action  = $_POST['action'] ?? '';

    if ($action === 'approve') {
        $pdo->prepare("UPDATE notes SET status='approved' WHERE id=?")
            ->execute([$note_id]);
    } elseif ($action === 'reject') {
        $reason = sanitize($_POST['reason'] ?? 'Does not meet guidelines.');
        $pdo->prepare("UPDATE notes SET status='rejected', rejection_reason=? WHERE id=?")
            ->execute([$reason, $note_id]);
    }
    redirect('/notes-platform/admin/approve_notes.php');
}

$pending = $pdo->query("
    SELECT n.*, u.name as uploader, u.email as uploader_email,
           s.name as subject_name, b.name as branch_name, sm.number as semester_num
    FROM notes n
    JOIN users u  ON n.user_id    = u.id
    JOIN subjects s  ON n.subject_id  = s.id
    JOIN branches b  ON n.branch_id   = b.id
    JOIN semesters sm ON n.semester_id = sm.id
    WHERE n.status = 'pending'
    ORDER BY n.created_at ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Notes — NotesHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="../index.php" class="nav-brand">📚 NotesHub</a>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="approve_notes.php" class="active">Approve Notes</a>
            <a href="reports_queue.php">Reports</a>
            <a href="manage_users.php">Users</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="admin-container">
    <div class="admin-header">
        <h1>Pending Notes</h1>
        <p><?= count($pending) ?> note(s) waiting for review</p>
    </div>

    <?php if (empty($pending)): ?>
        <div class="empty-state">
            <div class="empty-icon">✅</div>
            <h3>All caught up!</h3>
            <p>No pending notes to review.</p>
        </div>
    <?php else: ?>
        <?php foreach ($pending as $note): ?>
        <div class="approve-card">
            <div class="approve-info">
                <div class="approve-meta">
                    <span class="note-subject"><?= sanitize($note['subject_name']) ?></span>
                    <span class="note-sem">Sem <?= $note['semester_num'] ?></span>
                    <span class="note-sem"><?= sanitize($note['branch_name']) ?></span>
                    <span style="color:#888;font-size:0.8rem">
                        <?= formatFileSize($note['file_size']) ?>
                    </span>
                </div>
                <h3><?= sanitize($note['title']) ?></h3>
                <p class="approve-desc"><?= sanitize($note['description']) ?></p>
                <div class="approve-uploader">
                    Uploaded by <strong><?= sanitize($note['uploader']) ?></strong>
                    · <?= timeAgo($note['created_at']) ?>
                    <?php if ($note['tags']): ?>
                        · Tags: <em><?= sanitize($note['tags']) ?></em>
                    <?php endif; ?>
                </div>
            </div>

            <div class="approve-actions">
                <!-- Approve -->
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
                    <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn-success">✓ Approve</button>
                </form>

                <!-- Reject -->
                <form method="POST" onsubmit="return confirmReject(this)">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
                    <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="reason" class="reject-reason" value="">
                    <button type="submit" class="btn btn-danger">✗ Reject</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function confirmReject(form) {
    const reason = prompt('Enter rejection reason:');
    if (!reason) return false;
    form.querySelector('.reject-reason').value = reason;
    return true;
}
</script>

</body>
</html>