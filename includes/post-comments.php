<?php
// /includes/partials/post-comments.php
// Expects: $dbconn (PDO), $postId (int), session started

if (!isset($postId)) {
    echo '<div class="alert alert-danger">postId is missing.</div>';
    return;
}

$initialLimit = 5;

// Fetch first 5 comments
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

// Count total comments for "Show more" button
$countStmt = $dbconn->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
$countStmt->execute([(int)$postId]);
$totalComments = (int)$countStmt->fetchColumn();
$hasMore = $totalComments > $initialLimit;
?>

<div class="card mt-3">
    <div class="card-body">
        <h6 class="mb-3">Comments</h6>

        <div id="commentsList">
            <?php foreach ($comments as $c): ?>
                <div class="d-flex gap-3 border rounded p-2 mb-2">
                    <div class="text-center" style="min-width:72px;">
                        <img
                            src="<?php echo htmlspecialchars($c['Pfp'] ?: 'images/default-pfp.png'); ?>"
                            alt="pfp"
                            style="width:48px;height:48px;border-radius:50%;object-fit:cover;"
                        >
                        <div class="small mt-1"><?php echo htmlspecialchars($c['username']); ?></div>
                    </div>

                    <div class="flex-grow-1">
                        <p class="mb-1"><?php echo nl2br(htmlspecialchars($c['body'])); ?></p>
                        <small class="text-muted"><?php echo htmlspecialchars($c['created_at']); ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($hasMore): ?>
            <button id="loadMoreBtn" class="btn btn-outline-secondary w-100">Show more comments</button>
        <?php endif; ?>
    </div>
</div>
