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

<div class="card mt-3 shadow-sm border-0">
  <div class="card-body">
    <h6 class="mb-3 fw-semibold">Comments</h6>

    <form id="commentForm" class="mb-3">
      <input type="hidden" name="post_id" value="<?php echo (int)$postId; ?>">
      <div class="input-group input-group-sm">
        <input type="text" name="body" class="form-control" maxlength="500" placeholder="Write a comment..." <?php echo $isLoggedIn ? '' : 'disabled'; ?> required>
      <button type="submit" class="btn btn-primary px-3" <?php echo $isLoggedIn ? '' : 'disabled'; ?>>Post</button>
      </div>
    </form>

    <div id="commentsList">
      <?php foreach ($comments as $c): ?>
        <?php $canDeleteComment = $currentUserIsAdmin || ($currentUserId > 0 && (int) $c['author_id'] === $currentUserId); ?>
        <div class="d-flex gap-3 border rounded-3 p-2 mb-2 bg-light-subtle comment-item" id="comment-<?php echo (int) $c['id']; ?>">
          <div class="text-center" style="min-width:72px;">
            <?php if (!empty($c['avatar_path'])): ?>
              <img src="<?php echo htmlspecialchars(site_asset_url((string) $c['avatar_path'])); ?>" alt="pfp" class="rounded-circle border" style="width:48px;height:48px;object-fit:cover;">
            <?php else: ?>
              <div class="rounded-circle border bg-dark text-white d-grid place-items-center" style="width:48px;height:48px;display:grid;">Pfp</div>
            <?php endif; ?>
            <div class="small mt-1">
              <a href="Profile.php?user_id=<?php echo (int) ($c['author_id'] ?? 0); ?>" class="text-decoration-none text-reset">
                <?php echo htmlspecialchars($c['username']); ?>
              </a>
            </div>
          </div>
          <div class="flex-grow-1 comment-content">
            <p class="mb-1 comment-body"><?php echo nl2br(htmlspecialchars($c['body'])); ?></p>
            <small class="text-muted"><?php echo htmlspecialchars($c['created_at']); ?></small>
          </div>
          <div class="ms-auto align-self-start comment-actions">
            <?php if (!empty($c['username'])): ?>
              <button
                type="button"
                class="btn btn-sm btn-outline-primary"
                data-action="reply-comment"
                data-comment-user="<?php echo htmlspecialchars($c['username']); ?>"
              >
                Reply
              </button>
            <?php endif; ?>
            <?php if ($canDeleteComment): ?>
              <button type="button" class="btn btn-sm btn-outline-danger" data-action="delete-comment" data-comment-id="<?php echo (int) $c['id']; ?>">Delete</button>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <button id="loadMoreBtn" class="btn btn-outline-secondary w-100 <?php echo $hasMore ? '' : 'd-none'; ?>" data-offset="<?php echo count($comments); ?>" data-post-id="<?php echo (int)$postId; ?>">
      Show more comments
    </button>
  </div>
</div>
