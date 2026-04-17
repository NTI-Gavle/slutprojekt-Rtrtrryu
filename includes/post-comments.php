<?php
if (!isset($postId)) { echo '<div class="alert alert-danger">postId is missing.</div>'; return; }
require_once __DIR__ . '/../database/user_queries.php';

$initialLimit = 5;
$isLoggedIn = isset($_SESSION['user_id']);
$currentUserId = $isLoggedIn ? (int) $_SESSION['user_id'] : 0;
$currentUserIsAdmin = $isLoggedIn ? userHasAdminAccess($dbconn, $currentUserId) : false;

$comments = fetchCommentsForPost($dbconn, (int) $postId, (int) $initialLimit, 0);

$countStmt = $dbconn->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
$countStmt->execute([(int)$postId]);
$totalComments = (int)$countStmt->fetchColumn();
$hasMore = $totalComments > $initialLimit;
?>

<div class="card mt-3">
  <div class="card-body">
    <h6 class="mb-3">Comments</h6>

    <form id="commentForm" class="mb-3" onsubmit="return postComment(event)">
      <input type="hidden" name="post_id" value="<?php echo (int)$postId; ?>">
      <div class="input-group">
        <input type="text" name="body" class="form-control" maxlength="500" placeholder="Write a comment..." <?php echo $isLoggedIn ? '' : 'disabled'; ?> required>
        <button type="submit" class="btn btn-primary" <?php echo $isLoggedIn ? '' : 'disabled'; ?>>Post</button>
      </div>
    </form>

    <div id="commentsList">
      <?php foreach ($comments as $c): ?>
        <?php $canDeleteComment = $currentUserIsAdmin || ($currentUserId > 0 && (int) $c['author_id'] === $currentUserId); ?>
        <div class="d-flex gap-3 border rounded p-2 mb-2 comment-item" id="comment-<?php echo (int) $c['id']; ?>">
          <div class="text-center" style="min-width:72px;">
            <?php if (!empty($c['avatar_path'])): ?>
              <img src="<?php echo htmlspecialchars((string) $c['avatar_path']); ?>" alt="pfp" style="width:48px;height:48px;border-radius:50%;object-fit:cover;">
            <?php else: ?>
              <div style="width:48px;height:48px;border-radius:50%;background:#222;color:#fff;display:grid;place-items:center;">Pfp</div>
            <?php endif; ?>
            <div class="small mt-1"><?php echo htmlspecialchars($c['username']); ?></div>
          </div>
          <div class="flex-grow-1">
            <p class="mb-1"><?php echo nl2br(htmlspecialchars($c['body'])); ?></p>
            <small class="text-muted"><?php echo htmlspecialchars($c['created_at']); ?></small>
          </div>
          <?php if ($canDeleteComment): ?>
            <div>
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteComment(<?php echo (int) $c['id']; ?>)">Delete</button>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <button id="loadMoreBtn" class="btn btn-outline-secondary w-100 <?php echo $hasMore ? '' : 'd-none'; ?>" data-offset="<?php echo count($comments); ?>" data-post-id="<?php echo (int)$postId; ?>">
      Show more comments
    </button>
  </div>
</div>

<script>
const currentUserId = <?php echo (int) $currentUserId; ?>;
const currentUserIsAdmin = <?php echo $currentUserIsAdmin ? 'true' : 'false'; ?>;

function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
}

function renderComment(c) {
    const div = document.createElement('div');
    div.className = 'd-flex gap-3 border rounded p-2 mb-2 comment-item';
    div.id = `comment-${c.id}`;

    const avatarHtml = c.avatar_path
      ? `<img src="${escapeHtml(c.avatar_path)}" alt="pfp" style="width:48px;height:48px;border-radius:50%;object-fit:cover;">`
      : `<div style="width:48px;height:48px;border-radius:50%;background:#222;color:#fff;display:grid;place-items:center;">Pfp</div>`;

    const canDelete = Boolean(c.can_delete);
    const deleteHtml = canDelete
      ? `<div><button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteComment(${Number(c.id)})">Delete</button></div>`
      : '';

    div.innerHTML = `
        <div class="text-center" style="min-width:72px;">
            ${avatarHtml}
            <div class="small mt-1">${escapeHtml(c.username)}</div>
        </div>
        <div class="flex-grow-1">
            <p class="mb-1">${escapeHtml(c.body).replace(/\n/g, '<br>')}</p>
            <small class="text-muted">${escapeHtml(c.created_at)}</small>
        </div>
        ${deleteHtml}
    `;
    return div;
}

async function postComment(e) {
    e.preventDefault();

    const form = document.getElementById('commentForm');
    const list = document.getElementById('commentsList');
    const loadMoreBtn = document.getElementById('loadMoreBtn');

    const res = await fetch('./add-comment.php', {
        method: 'POST',
        body: new FormData(form)
    });

    const text = await res.text();
    let data;
    try { data = JSON.parse(text); } catch { alert(text); return false; }

    if (!data.ok) { alert(data.error || 'Could not post'); return false; }

    list.prepend(renderComment(data.comment));
    form.reset();

    if (loadMoreBtn) {
        loadMoreBtn.dataset.offset = String(Number(loadMoreBtn.dataset.offset || 0) + 1);
    }

    return false;
}

async function deleteComment(commentId) {
    if (!confirm('Delete this comment?')) return;

    const payload = new FormData();
    payload.append('comment_id', String(commentId));

    const res = await fetch('./delete-comment.php', {
      method: 'POST',
      body: payload
    });

    const text = await res.text();
    let data;
    try { data = JSON.parse(text); } catch { alert(text); return; }

    if (!data.ok) {
      alert(data.error || 'Could not delete comment');
      return;
    }

    const row = document.getElementById(`comment-${commentId}`);
    if (row) row.remove();
}

document.addEventListener('DOMContentLoaded', () => {
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const list = document.getElementById('commentsList');
    if (!loadMoreBtn) return;

    loadMoreBtn.addEventListener('click', async () => {
        const postId = loadMoreBtn.dataset.postId;
        const offset = loadMoreBtn.dataset.offset || 0;

        const res = await fetch(`./comments.php?post_id=${postId}&offset=${offset}&limit=5`);
        const text = await res.text();

        let data;
        try { data = JSON.parse(text); } catch { alert(text); return; }
        if (!data.ok) { alert(data.error || 'Could not load comments'); return; }

        data.comments.forEach(c => list.appendChild(renderComment(c)));

        loadMoreBtn.dataset.offset = String(Number(offset) + data.comments.length);
        if (!data.hasMore) loadMoreBtn.classList.add('d-none');
    });
});
</script>
