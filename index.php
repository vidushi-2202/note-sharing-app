<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

$pdo = getDB();

// Filters
$subject_id  = $_GET['subject'] ?? '';
$semester_id = $_GET['semester'] ?? '';
$branch_id   = $_GET['branch'] ?? '';
$sort        = $_GET['sort'] ?? 'newest';
$search      = $_GET['q'] ?? '';

// Build query
$where  = ["n.status = 'approved'"];
$params = [];

if ($subject_id)  { $where[] = "n.subject_id = ?";  $params[] = $subject_id; }
if ($semester_id) { $where[] = "n.semester_id = ?"; $params[] = $semester_id; }
if ($branch_id)   { $where[] = "n.branch_id = ?";   $params[] = $branch_id; }
if ($search)      { $where[] = "(n.title LIKE ? OR n.description LIKE ? OR n.tags LIKE ?)";
                    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

$whereSQL = implode(' AND ', $where);

$orderBy = match($sort) {
    'downloads' => 'n.download_count DESC',
    'rating'    => 'n.avg_rating DESC',
    default     => 'n.created_at DESC'
};

$sql = "SELECT n.*, u.name as uploader, s.name as subject_name,
               b.name as branch_name, sm.number as semester_num
        FROM notes n
        JOIN users u ON n.user_id = u.id
        JOIN subjects s ON n.subject_id = s.id
        JOIN branches b ON n.branch_id = b.id
        JOIN semesters sm ON n.semester_id = sm.id
        WHERE $whereSQL
        ORDER BY $orderBy
        LIMIT 20";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notes = $stmt->fetchAll();

// Sidebar data
$subjects  = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll();
$semesters = $pdo->query("SELECT * FROM semesters ORDER BY number")->fetchAll();
$branches  = $pdo->query("SELECT * FROM branches ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes Platform — Browse Notes</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-brand">📚 NotesHub</a>
        <div class="nav-search">
            <form method="GET" action="index.php">
                <input type="text" name="q" placeholder="Search notes..." value="<?= sanitize($search) ?>">
                <button type="submit">🔍</button>
            </form>
        </div>
        <div class="nav-links">
            <?php if (isLoggedIn()): ?>
                <a href="notes/upload.php" class="btn btn-primary">+ Upload</a>
                <a href="user/profile.php"><?= sanitize($_SESSION['name']) ?></a>
                <?php if (isAdmin()): ?>
                    <a href="admin/dashboard.php">Admin</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php" class="btn btn-primary">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Main Layout -->
<div class="main-container">

    <!-- Sidebar Filters -->
    <aside class="sidebar">
        <h3>Filter Notes</h3>
        <form method="GET" action="index.php">
            <?php if ($search): ?>
                <input type="hidden" name="q" value="<?= sanitize($search) ?>">
            <?php endif; ?>

            <div class="filter-group">
                <label>Subject</label>
                <select name="subject" onchange="this.form.submit()">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $subject_id == $s['id'] ? 'selected' : '' ?>>
                            <?= sanitize($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Semester</label>
                <select name="semester" onchange="this.form.submit()">
                    <option value="">All Semesters</option>
                    <?php foreach ($semesters as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $semester_id == $s['id'] ? 'selected' : '' ?>>
                            Semester <?= $s['number'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Branch</label>
                <select name="branch" onchange="this.form.submit()">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $branch_id == $b['id'] ? 'selected' : '' ?>>
                            <?= sanitize($b['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($subject_id || $semester_id || $branch_id || $search): ?>
                <a href="index.php" class="clear-filters">✕ Clear Filters</a>
            <?php endif; ?>
        </form>

        <!-- Sort -->
        <div class="filter-group" style="margin-top:1.5rem">
            <label>Sort By</label>
            <div class="sort-links">
                <a href="?<?= http_build_query(array_merge($_GET, ['sort'=>'newest'])) ?>"
                   class="<?= $sort === 'newest' ? 'active' : '' ?>">Newest</a>
                <a href="?<?= http_build_query(array_merge($_GET, ['sort'=>'downloads'])) ?>"
                   class="<?= $sort === 'downloads' ? 'active' : '' ?>">Most Downloaded</a>
                <a href="?<?= http_build_query(array_merge($_GET, ['sort'=>'rating'])) ?>"
                   class="<?= $sort === 'rating' ? 'active' : '' ?>">Highest Rated</a>
            </div>
        </div>
    </aside>

    <!-- Notes Grid -->
    <main class="notes-main">
        <div class="notes-header">
            <h2><?= $search ? "Results for \"" . sanitize($search) . "\"" : "Browse Notes" ?></h2>
            <span class="notes-count"><?= count($notes) ?> notes found</span>
        </div>

        <?php if (empty($notes)): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3>No notes found</h3>
                <p>Try different filters or be the first to upload!</p>
                <?php if (isLoggedIn()): ?>
                    <a href="notes/upload.php" class="btn btn-primary">Upload Note</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="notes-grid">
                <?php foreach ($notes as $note): ?>
                <div class="note-card">
                    <div class="note-thumb">
                        <?php if ($note['thumbnail']): ?>
                            <img src="uploads/thumbnails/<?= $note['thumbnail'] ?>" alt="Preview">
                        <?php else: ?>
                            <div class="thumb-placeholder">
                                <?= strtoupper(pathinfo($note['file_path'], PATHINFO_EXTENSION)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="note-info">
                        <div class="note-meta">
                            <span class="note-subject"><?= sanitize($note['subject_name']) ?></span>
                            <span class="note-sem">Sem <?= $note['semester_num'] ?></span>
                        </div>
                        <h3 class="note-title">
                            <a href="notes/view.php?id=<?= $note['id'] ?>">
                                <?= sanitize($note['title']) ?>
                            </a>
                        </h3>
                        <p class="note-desc"><?= sanitize(substr($note['description'], 0, 80)) ?>...</p>
                        <div class="note-footer">
                            <span class="note-stars"><?= renderStars($note['avg_rating']) ?></span>
                            <span class="note-downloads">⬇ <?= $note['download_count'] ?></span>
                            <span class="note-uploader">by <?= sanitize($note['uploader']) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>
<script src="assets/js/search.js"></script>

</body>
</html>