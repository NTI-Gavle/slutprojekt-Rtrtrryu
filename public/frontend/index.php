<?php
$pageTitle = "Home";
$bodyStyle = 'background-color: darkmagenta';
require_once __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../database/user_queries.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userIsAdult = false;
$isAdmin = false;
$adminMessage = null;
$currentUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

if ($currentUserId > 0) {
    $userIsAdult = getUserAge($dbconn, $currentUserId) >= 18;
    $isAdmin = userHasAdminAccess($dbconn, $currentUserId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post_id'])) {
    $incomingToken = (string) ($_POST['csrf_token'] ?? '');
    $postIdToDelete = filter_input(INPUT_POST, 'delete_post_id', FILTER_VALIDATE_INT);

    if (!hash_equals((string) $_SESSION['csrf_token'], $incomingToken)) {
        $adminMessage = 'Security token mismatch. Refresh the page and try again.';
    } elseif ($currentUserId <= 0) {
        $adminMessage = 'You need to be logged in.';
    } elseif (!$postIdToDelete) {
        $adminMessage = 'Invalid post id.';
    } elseif (!canUserDeletePost($dbconn, $currentUserId, (int) $postIdToDelete)) {
        $adminMessage = 'You can only delete your own posts.';
    } else {
        $deleted = deletePostForUser($dbconn, $currentUserId, (int) $postIdToDelete);
        $adminMessage = $deleted ? 'Post deleted.' : 'Could not delete the post.';
    }
}

$posts = [];
$postsTable = resolveTableName($dbconn, ['posts', 'post']);
$userMeta = getUserTableMeta($dbconn);

if ($postsTable !== null && $userMeta !== null) {
    $postColumns = getTableColumns($dbconn, $postsTable);
    $postCreatorColumn = findColumn($postColumns, ['creator_id', 'user_id', 'author_id']);
    $avatarSelect = $userMeta['avatar_column'] !== null
        ? "u.`{$userMeta['avatar_column']}` AS avatar_path"
        : "NULL AS avatar_path";

    if ($postCreatorColumn !== null) {
        $sql = "
            SELECT p.*, u.`{$userMeta['name_column']}` AS username, {$avatarSelect}
            FROM `{$postsTable}` p
            JOIN `{$userMeta['table']}` u ON p.`{$postCreatorColumn}` = u.`{$userMeta['id_column']}`
            ORDER BY p.created_at DESC
        ";
        $posts = $dbconn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>


<div class="container py-4" style="background-color: darkviolet;">
    <div><?php include __DIR__ . '/../../includes/menu.php'; ?></div>

    <div class="post-window">

        <?php if ($currentUserId > 0): ?>
            <div class="d-flex justify-content-end mb-2">
                <a href="Post.php" class="btn btn-primary">Make a post</a>
            </div>
        <?php else: ?>
            <div class="alert alert-info py-2 mb-2">
                <a href="login.php" class="alert-link">Log in to make a post</a>
            </div>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
            <div class="alert alert-primary py-2 mb-2">Admin mode active: you can delete posts.</div>
        <?php endif; ?>

        <?php if ($adminMessage !== null): ?>
            <div class="alert alert-warning py-2 mb-2"><?php echo htmlspecialchars($adminMessage); ?></div>
        <?php endif; ?>

        <?php foreach ($posts as $post): ?>
            <?php $restricted = !empty($post['adultcheck']) && ($currentUserId <= 0 || !$userIsAdult); ?>
            <?php $canDeletePost = $currentUserId > 0 && ($isAdmin || (int) ($post['creator_id'] ?? 0) === $currentUserId); ?>
            <div class="post feed-post shadow-sm position-relative">
                <a href="PostViewer.php?post_id=<?php echo (int) $post['id']; ?>" class="post-open-link" aria-label="Open post"></a>
                <?php if ($canDeletePost): ?>
                    <form method="POST" action="index.php" class="admin-delete-form" data-confirm="Delete this post?">
                        <input type="hidden" name="delete_post_id" value="<?php echo (int) $post['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $_SESSION['csrf_token']); ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                <?php endif; ?>

                <div class="post-header <?php echo $restricted ? 'post-blurred' : ''; ?>">
                    <div class="post-author-row mb-1">
                        <?php if (!empty($post['avatar_path'])): ?>
                            <img src="<?php echo htmlspecialchars(site_asset_url((string) $post['avatar_path'])); ?>" alt="Profile picture" class="post-author-avatar">
                        <?php else: ?>
                            <div class="post-author-avatar post-author-avatar-fallback">Pfp</div>
                        <?php endif; ?>
                        <div class="small text-light-emphasis position-relative">
                            <a href="Profile.php?user_id=<?php echo (int) ($post['creator_id'] ?? 0); ?>" class="text-decoration-none text-reset post-author-link">
                                @<?php echo htmlspecialchars((string) ($post['username'] ?? 'unknown')); ?>
                            </a>
                        </div>
                    </div>
                    <div class="h6 mb-0"><?php echo htmlspecialchars((string) ($post['title'] ?? 'Untitled')); ?></div>
                    <?php if (!empty($post['adultcheck'])): ?>
                        <span class="badge bg-danger ms-2">18+</span>
                    <?php endif; ?>
                </div>

                <div class="post-content <?php echo $restricted ? 'post-blurred' : ''; ?>">
                    <?php echo nl2br(htmlspecialchars((string) ($post['body'] ?? ''))); ?>
                </div>

                <?php if (!empty($post['image_path'])): ?>
                    <div class="post-image <?php echo $restricted ? 'post-blurred' : ''; ?>">
                        <img src="<?php echo htmlspecialchars(site_asset_url((string) $post['image_path'])); ?>" alt="Post image">
                    </div>
                <?php endif; ?>

                <?php if ($restricted): ?>
                    <div class="adult-overlay">
                        <p class="mb-1 fw-semibold">This post is 18+ only</p>
                        <?php if ($currentUserId <= 0): ?>
                            <a href="login.php">Log in to view</a>
                        <?php else: ?>
                            <p>You must be 18 or older to view this</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>

    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

