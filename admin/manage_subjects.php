<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireAdmin();
$pdo = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';

    if ($action === 'add_subject') {
        $name = sanitize($_POST['name'] ?? '');
        if ($name) {
            try {
                $pdo->prepare("INSERT INTO subjects (name) VALUES (?)")->execute([$name]);
                $success = "Subject '$name' added!";
            } catch (Exception $e) {
                $error = "Subject already exists.";
            }
        }
    } elseif ($action === 'add_branch') {
        $name = sanitize($_POST['name'] ?? '');
        if ($name) {
            try {
                $pdo->prepare("INSERT INTO branches (name) VALUES (?)")->execute([$name]);
                $success = "Branch '$name' added!";
            } catch (Exception $e) {
                $error = "Branch already exists.";
            }
        }
    } elseif ($action === 'delete_subject') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM subjects WHERE id=?")->execute([$id]);
        $success = "Subject deleted.";
    } elseif ($action === 'delete_branch') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM branches WHERE id=?")->execute([$id]);
        $success = "Branch deleted.";
    }
}

$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll();
$branches = $pdo->query("SELECT * FROM branches ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects — NotesHub</title>
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
            <a href="manage_users.php">Users</a>
            <a href="manage_subjects.php" class="active">Subjects</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="admin-container">
    <div class="admin-header">
        <h1>📚 Manage Subjects & Branches</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <div class="admin-grid">
        <!-- Subjects -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2>Subjects (<?= count($subjects) ?>)</h2>
            </div>
            <div style="padding:1rem 1.25rem">
                <form method="POST" style="display:flex;gap:0.5rem;margin-bottom:1rem">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
                    <input type="hidden" name="action" value="add_subject">
                    <input type="text" name="name" placeholder="New subject name"
                           style="flex:1;padding:0.5rem;border:1.5px solid #e0e0e0;border-radius:8px;outline:none">
                    <button type="submit" class="btn btn-primary">Add</button>
                </form>
                <?php foreach ($subjects as $s): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;
                            padding:0.6rem 0;border-bottom:1px solid #f8f8f8">
                    <span style="font-size:0.9rem"><?= sanitize($s['name']) ?></span>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
                        <input type="hidden" name="action" value="delete_subject">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button type="submit" class="btn btn-danger"
                                style="font-size:0.75rem;padding:3px 8px"
                                onclick="return confirm('Delete this subject?')">✕</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Branches -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2>Branches (<?= count($branches) ?>)</h2>
            </div>
            <div style="padding:1rem 1.25rem">
                <form method="POST" style="display:flex;gap:0.5rem;margin-bottom:1rem">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
                    <input type="hidden" name="action" value="add_branch">
                    <input type="text" name="name" placeholder="New branch name"
                           style="flex:1;padding:0.5rem;border:1.5px solid #e0e0e0;border-radius:8px;outline:none">
                    <button type="submit" class="btn btn-primary">Add</button>
                </form>
                <?php foreach ($branches as $b): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;
                            padding:0.6rem 0;border-bottom:1px solid #f8f8f8">
                    <span style="font-size:0.9rem"><?= sanitize($b['name']) ?></span>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
                        <input type="hidden" name="action" value="delete_branch">
                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                        <button type="submit" class="btn btn-danger"
                                style="font-size:0.75rem;padding:3px 8px"
                                onclick="return confirm('Delete this branch?')">✕</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>