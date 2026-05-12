<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';
require_once '../includes/uploader.php';

requireLogin();

$pdo = getDB();
$error = '';
$success = '';

$subjects  = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll();
$semesters = $pdo->query("SELECT * FROM semesters ORDER BY number")->fetchAll();
$branches  = $pdo->query("SELECT * FROM branches ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF($_POST['csrf_token'] ?? '');

    $title       = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $subject_id  = (int)($_POST['subject_id'] ?? 0);
    $semester_id = (int)($_POST['semester_id'] ?? 0);
    $branch_id   = (int)($_POST['branch_id'] ?? 0);
    $tags        = sanitize($_POST['tags'] ?? '');
    $visibility  = $_POST['visibility'] === 'college' ? 'college' : 'public';

    if (!$title || !$subject_id || !$semester_id || !$branch_id) {
        $error = 'Please fill in all required fields.';
    } elseif (empty($_FILES['file']['name'])) {
        $error = 'Please select a file to upload.';
    } else {
        $upload = uploadNote($_FILES['file']);
        if (!$upload['success']) {
            $error = $upload['error'];
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO notes
                (user_id, title, description, subject_id, semester_id, branch_id,
                 tags, file_path, file_type, file_size, thumbnail, visibility, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $title, $description,
                $subject_id, $semester_id, $branch_id,
                $tags,
                $upload['filename'],
                $upload['mime'],
                $upload['size'],
                $upload['thumbnail'],
                $visibility
            ]);
            $success = 'Note uploaded successfully! It will appear after admin approval.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Note — NotesHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="../index.php" class="nav-brand">📚 NotesHub</a>
        <div class="nav-links">
            <a href="../index.php">Browse</a>
            <a href="../user/profile.php"><?= sanitize($_SESSION['name']) ?></a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="upload-container">
    <div class="upload-box">
        <h1 class="upload-title">Upload Notes</h1>
        <p class="upload-subtitle">Share your study material with the community</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">

            <div class="form-group">
                <label>Title <span class="required">*</span></label>
                <input type="text" name="title" placeholder="e.g. Data Structures Complete Notes" required
                       value="<?= sanitize($_POST['title'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"
                          placeholder="Brief description of what these notes cover..."><?= sanitize($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Subject <span class="required">*</span></label>
                    <select name="subject_id" required>
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>"
                                <?= ($_POST['subject_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                                <?= sanitize($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Semester <span class="required">*</span></label>
                    <select name="semester_id" required>
                        <option value="">Select Semester</option>
                        <?php foreach ($semesters as $s): ?>
                            <option value="<?= $s['id'] ?>"
                                <?= ($_POST['semester_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                                Semester <?= $s['number'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Branch <span class="required">*</span></label>
                    <select name="branch_id" required>
                        <option value="">Select Branch</option>
                        <?php foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>"
                                <?= ($_POST['branch_id'] ?? '') == $b['id'] ? 'selected' : '' ?>>
                                <?= sanitize($b['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Tags</label>
                <input type="text" name="tags" placeholder="e.g. arrays, linked-list, sorting"
                       value="<?= sanitize($_POST['tags'] ?? '') ?>">
                <small>Separate tags with commas</small>
            </div>

            <div class="form-group">
                <label>Visibility</label>
                <select name="visibility">
                    <option value="public">Public — visible to everyone</option>
                    <option value="college">College Only</option>
                </select>
            </div>

            <div class="form-group">
                <label>File <span class="required">*</span></label>
                <div class="file-drop" id="fileDrop">
                    <div class="file-drop-icon">📄</div>
                    <div class="file-drop-text">Click to select or drag & drop</div>
                    <div class="file-drop-hint">PDF, DOCX, JPG, PNG — Max 20MB</div>
                    <input type="file" name="file" id="fileInput" accept=".pdf,.docx,.doc,.jpg,.jpeg,.png" required>
                </div>
                <div id="fileName" class="file-name"></div>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Upload Note
            </button>
        </form>
    </div>
</div>

<script>
const input = document.getElementById('fileInput');
const label = document.getElementById('fileName');
const drop  = document.getElementById('fileDrop');

input.addEventListener('change', () => {
    if (input.files[0]) {
        label.textContent = '✅ ' + input.files[0].name;
        drop.classList.add('has-file');
    }
});
</script>

</body>
</html>