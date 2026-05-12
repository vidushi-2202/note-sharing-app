<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

if (!$id) redirect('/notes-platform/index.php');

// Get note
$stmt = $pdo->prepare("
    SELECT n.*, u.name as uploader, u.id as uploader_id,
           s.name as subject_name, b.name as branch_name, sm.number as semester_num
    FROM notes n
    JOIN users u   ON n.user_id    = u.id
    JOIN subjects s   ON n.subject_id  = s.id
    JOIN branches b   ON n.branch_id   = b.id
    JOIN semesters sm ON n.semester_id = sm.id
    WHERE n.id = ? AND n.status = 'approved'
");
$stmt->execute([$id]);
$note = $stmt->fetch();

if (!$note) redirect('/notes-platform/index.php');

// Get comments
$comments = $pdo->prepare("
    SELECT c.*, u.name as commenter
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.note_id = ? AND c.parent_id IS NULL
    ORDER BY c.created_at DESC
");
$comments->execute([$id]);
$comments = $comments->fetchAll();

// Get replies for each comment
$replies = [];
foreach ($comments as $c) {
    $r = $pdo->prepare("
        SELECT c.*, u.name as commenter
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.parent_id = ?
        ORDER BY c.created_at ASC
    ");
    $r->execute([$c['id']]);
    $replies[$c['id']] = $r->fetchAll();
}

// Get user's rating
$userRating = 0;
$isBookmarked = false;
if (isLoggedIn()) {
    $r = $pdo->prepare("SELECT stars FROM ratings WHERE note_id=? AND user_id=?");
    $r->execute([$id, $_SESSION['user_id']]);
    $userRating = $r->fetchColumn() ?: 0;

    $b = $pdo->prepare("SELECT id FROM bookmarks WHERE note_id=? AND user_id=?");
    $b->execute([$id, $_SESSION['user_id']]);
    $isBookmarked = (bool)$b->fetchColumn();
}

// Related notes
$related = $pdo->prepare("
    SELECT n.*, s.name as subject_name
    FROM notes n
    JOIN subjects s ON n.subject_id = s.id
    WHERE n.subject_id = ? AND n.id != ? AND n.status = 'approved'
    LIMIT 4
");
$related->execute([$note['subject_id'], $id]);
$related = $related->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($note['title']) ?> — NotesHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="../index.php" class="nav-brand">📚 NotesHub</a>
        <div class="nav-links">
            <a href="../index.php">Browse</a>
            <?php if (isLoggedIn()): ?>
                <a href="../notes/upload.php" class="btn btn-primary">+ Upload</a>
                <a href="../user/profile.php"><?= sanitize($_SESSION['name']) ?></a>
                <?php if (isAdmin()): ?>
                    <a href="../admin/dashboard.php">Admin</a>
                <?php endif; ?>
                <a href="../logout.php">Logout</a>
            <?php else: ?>
                <a href="../login.php">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="view-container">
    <div class="view-main">

        <!-- Note Header -->
        <div class="view-header">
            <div class="note-meta">
                <span class="note-subject"><?= sanitize($note['subject_name']) ?></span>
                <span class="note-sem">Sem <?= $note['semester_num'] ?></span>
                <span class="note-sem"><?= sanitize($note['branch_name']) ?></span>
                <span class="note-sem"><?= strtoupper(pathinfo($note['file_path'], PATHINFO_EXTENSION)) ?></span>
            </div>
            <h1 class="view-title"><?= sanitize($note['title']) ?></h1>
            <p class="view-desc"><?= sanitize($note['description']) ?></p>

            <div class="view-info">
                <span>👤 <?= sanitize($note['uploader']) ?></span>
                <span>📅 <?= timeAgo($note['created_at']) ?></span>
                <span>📦 <?= formatFileSize($note['file_size']) ?></span>
                <span>⬇ <?= $note['download_count'] ?> downloads</span>
                <span>⭐ <?= number_format($note['avg_rating'], 1) ?> / 5</span>
            </div>

            <?php if ($note['tags']): ?>
            <div class="view-tags">
                <?php foreach (explode(',', $note['tags']) as $tag): ?>
                    <span class="tag"><?= sanitize(trim($tag)) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="view-actions">
                <a href="download.php?id=<?= $note['id'] ?>" class="btn btn-primary">
                    ⬇ Download
                </a>
                <?php if (isLoggedIn()): ?>
                <button class="btn btn-bookmark <?= $isBookmarked ? 'bookmarked' : '' ?>"
                        id="bookmarkBtn"
                        onclick="toggleBookmark(<?= $note['id'] ?>)">
                    <?= $isBookmarked ? '❤️ Saved' : '🤍 Save' ?>
                </button>
                <?php endif; ?>
                <?php if (isLoggedIn() && $_SESSION['user_id'] == $note['uploader_id']): ?>
                <a href="edit.php?id=<?= $note['id'] ?>" class="btn">✏️ Edit</a>
                <?php endif; ?>
                <?php if (isAdmin()): ?>
                <a href="../admin/approve_notes.php" class="btn btn-danger">🗑 Manage</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Star Rating -->
        <?php if (isLoggedIn()): ?>
        <div class="rating-box">
            <p>Rate this note:</p>
            <div class="star-rating" id="starRating">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="star <?= $i <= $userRating ? 'active' : '' ?>"
                      data-value="<?= $i ?>"
                      onclick="rateNote(<?= $note['id'] ?>, <?= $i ?>)">★</span>
                <?php endfor; ?>
            </div>
            <span id="ratingMsg" style="font-size:0.85rem;color:#888;margin-left:8px">
                <?= $userRating ? "You rated $userRating stars" : "Click to rate" ?>
            </span>
        </div>
        <?php endif; ?>

        <!-- Comments Section -->
        <div class="comments-section">
            <h2>Comments (<?= count($comments) ?>)</h2>

            <?php if (isLoggedIn()): ?>
            <form class="comment-form" id="commentForm">
                <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
                <input type="hidden" name="parent_id" value="">
                <textarea name="body" rows="3" placeholder="Write a comment..."></textarea>
                <button type="submit" class="btn btn-primary">Post Comment</button>
            </form>
            <?php else: ?>
                <p class="login-to-comment">
                    <a href="../login.php">Login</a> to leave a comment.
                </p>
            <?php endif; ?>

            <div id="commentsList">
            <?php foreach ($comments as $c): ?>
            <div class="comment" id="comment-<?= $c['id'] ?>">
                <div class="comment-header">
                    <span class="comment-author"><?= sanitize($c['commenter']) ?></span>
                    <span class="comment-time"><?= timeAgo($c['created_at']) ?></span>
                </div>
                <div class="comment-body"><?= sanitize($c['body']) ?></div>
                <?php if (isLoggedIn()): ?>
                <button class="reply-btn"
                        onclick="showReplyForm(<?= $c['id'] ?>)">↩ Reply</button>
                <div class="reply-form" id="reply-<?= $c['id'] ?>" style="display:none">
                    <textarea rows="2" placeholder="Write a reply..."></textarea>
                    <button class="btn btn-primary"
                            onclick="postReply(<?= $note['id'] ?>, <?= $c['id'] ?>)">
                        Post Reply
                    </button>
                </div>
                <?php endif; ?>

                <!-- Replies -->
                <?php if (!empty($replies[$c['id']])): ?>
                <div class="replies">
                    <?php foreach ($replies[$c['id']] as $r): ?>
                    <div class="comment reply">
                        <div class="comment-header">
                            <span class="comment-author"><?= sanitize($r['commenter']) ?></span>
                            <span class="comment-time"><?= timeAgo($r['created_at']) ?></span>
                        </div>
                        <div class="comment-body"><?= sanitize($r['body']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            </div>
        </div>

    </div>

    <!-- Sidebar -->
    <aside class="view-sidebar">
        <div class="sidebar-card">
            <h3>File Info</h3>
            <div class="file-info-list">
                <div class="file-info-row">
                    <span>Type</span>
                    <span><?= strtoupper(pathinfo($note['file_path'], PATHINFO_EXTENSION)) ?></span>
                </div>
                <div class="file-info-row">
                    <span>Size</span>
                    <span><?= formatFileSize($note['file_size']) ?></span>
                </div>
                <div class="file-info-row">
                    <span>Downloads</span>
                    <span><?= $note['download_count'] ?></span>
                </div>
                <div class="file-info-row">
                    <span>Rating</span>
                    <span><?= renderStars($note['avg_rating']) ?></span>
                </div>
                <div class="file-info-row">
                    <span>Visibility</span>
                    <span><?= ucfirst($note['visibility']) ?></span>
                </div>
            </div>
        </div>

        <?php if (!empty($related)): ?>
        <div class="sidebar-card">
            <h3>Related Notes</h3>
            <?php foreach ($related as $r): ?>
            <a href="view.php?id=<?= $r['id'] ?>" class="related-note">
                <div class="related-thumb">
                    <?= strtoupper(pathinfo($r['file_path'], PATHINFO_EXTENSION)) ?>
                </div>
                <div class="related-info">
                    <div class="related-title"><?= sanitize(substr($r['title'], 0, 40)) ?></div>
                    <div class="related-subject"><?= sanitize($r['subject_name']) ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </aside>
</div>

<script>
// Star rating
function rateNote(noteId, stars) {
    fetch('../api/rate.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `note_id=${noteId}&stars=${stars}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.querySelectorAll('.star').forEach((s, i) => {
                s.classList.toggle('active', i < stars);
            });
            document.getElementById('ratingMsg').textContent = `You rated ${stars} stars`;
        }
    });
}

// Bookmark toggle
function toggleBookmark(noteId) {
    fetch('../api/bookmark.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `note_id=${noteId}`
    })
    .then(r => r.json())
    .then(data => {
        const btn = document.getElementById('bookmarkBtn');
        if (data.bookmarked) {
            btn.textContent = '❤️ Saved';
            btn.classList.add('bookmarked');
        } else {
            btn.textContent = '🤍 Save';
            btn.classList.remove('bookmarked');
        }
    });
}

// Comment submit
document.getElementById('commentForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const body = this.querySelector('textarea').value.trim();
    const noteId = this.querySelector('[name=note_id]').value;
    if (!body) return;

    fetch('../api/comment.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `note_id=${noteId}&body=${encodeURIComponent(body)}&parent_id=`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('commentsList').insertAdjacentHTML('afterbegin', data.html);
            this.querySelector('textarea').value = '';
        }
    });
});

// Reply form toggle
function showReplyForm(commentId) {
    const form = document.getElementById('reply-' + commentId);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

// Post reply
function postReply(noteId, parentId) {
    const textarea = document.querySelector(`#reply-${parentId} textarea`);
    const body = textarea.value.trim();
    if (!body) return;

    fetch('../api/comment.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `note_id=${noteId}&body=${encodeURIComponent(body)}&parent_id=${parentId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            let repliesDiv = document.querySelector(`#comment-${parentId} .replies`);
            if (!repliesDiv) {
                repliesDiv = document.createElement('div');
                repliesDiv.className = 'replies';
                document.getElementById('comment-' + parentId).appendChild(repliesDiv);
            }
            repliesDiv.insertAdjacentHTML('beforeend', data.html);
            textarea.value = '';
            document.getElementById('reply-' + parentId).style.display = 'none';
        }
    });
}
</script>

</body>
</html>