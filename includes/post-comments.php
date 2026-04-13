<?php
if (!isset($postId)) { echo '<div class="alert alert-danger">postId is missing.</div>'; return; }

$initialLimit = 5;
$isLoggedIn = isset($_SESSION['user_id']);

$stmt = $dbconn->prepare("
    SELECT c.id, c.body, c.created_at, u.namn AS username, u.Pfp
    FROM comments c
    JOIN `användare` u ON u.id = c.author_id
    WHERE c.post_id = ?
    ORDER BY c.created_at DESC
    LIMIT ?
");
$stmt->bindValue(1, (int)$postId, PDO::PARAM_INT);
$stmt->bindValue(2, (int)$initialLimit, PDO::PARAM_INT);
$stmt->execute();
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <div class="d-flex gap-3 border rounded p-2 mb-2">
          <div class="text-center" style="min-width:72px;">
            <img src="<?php echo htmlspecialchars($c['Pfp'] ?: 'images/default-pfp.png'); ?>" alt="pfp" style="width:48px;height:48px;border-radius:50%;object-fit:cover;">
            <div class="small mt-1"><?php echo htmlspecialchars($c['username']); ?></div>
          </div>
          <div class="flex-grow-1">
            <p class="mb-1"><?php echo nl2br(htmlspecialchars($c['body'])); ?></p>
            <small class="text-muted"><?php echo htmlspecialchars($c['created_at']); ?></small>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <button id="loadMoreBtn" class="btn btn-outline-secondary w-100 <?php echo $hasMore ? '' : 'd-none'; ?>" data-offset="<?php echo count($comments); ?>" data-post-id="<?php echo (int)$postId; ?>">
      Show more comments
    </button>
  </div>
</div>

<script>
function renderComment(c) {
    const div = document.createElement('div');
    div.className = 'd-flex gap-3 border rounded p-2 mb-2';
    div.innerHTML = `
        <div class="text-center" style="min-width:72px;">
            <img src="${c.Pfp ? c.Pfp : 'images/default-pfp.png'}" alt="pfp" style="width:48px;height:48px;border-radius:50%;object-fit:cover;">
            <div class="small mt-1">${c.username}</div>
        </div>
        <div class="flex-grow-1">
            <p class="mb-1">${String(c.body).replace(/\n/g, '<br>')}</p>
            <small class="text-muted">${c.created_at}</small>
        </div>
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
