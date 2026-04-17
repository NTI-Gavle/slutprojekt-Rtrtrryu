<?php
$pageTitle = "Home";
require_once __DIR__ . '/../includes/header.php';
include('../database/db.php');
require_once __DIR__ . '/../database/user_queries.php';

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

    if ($postCreatorColumn !== null) {
        $sql = "
            SELECT p.*, u.`{$userMeta['name_column']}` AS username
            FROM `{$postsTable}` p
            JOIN `{$userMeta['table']}` u ON p.`{$postCreatorColumn}` = u.`{$userMeta['id_column']}`
            ORDER BY p.created_at DESC
        ";
        $posts = $dbconn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<body>

<link rel="stylesheet" href="css/style.css">

<div class="container py-4">
    <div><?php include('../includes/menu.php'); ?></div>

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
            <div class="post feed-post shadow-sm" style="position:relative;">
                <?php if ($canDeletePost): ?>
                    <form method="POST" action="index.php" class="admin-delete-form" onsubmit="return confirm('Delete this post?');">
                        <input type="hidden" name="delete_post_id" value="<?php echo (int) $post['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $_SESSION['csrf_token']); ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                <?php endif; ?>

                <a href="PostViewer.php?post_id=<?php echo (int) $post['id']; ?>" class="text-decoration-none link-dark d-block">
                    <div class="post-header" style="<?php echo $restricted ? 'filter:blur(60px);' : ''; ?>">
                        <div class="small text-light-emphasis mb-1">@<?php echo htmlspecialchars((string) ($post['username'] ?? 'unknown')); ?></div>
                        <div class="h6 mb-0"><?php echo htmlspecialchars((string) ($post['title'] ?? 'Untitled')); ?></div>
                        <?php if (!empty($post['adultcheck'])): ?>
                            <span class="badge bg-danger ms-2">18+</span>
                        <?php endif; ?>
                    </div>

                    <div class="post-content" style="<?php echo $restricted ? 'filter:blur(6px);user-select:none;' : ''; ?>">
                        <?php echo nl2br(htmlspecialchars((string) ($post['body'] ?? ''))); ?>
                    </div>

                    <?php if (!empty($post['image_path'])): ?>
                        <div class="post-image" style="<?php echo $restricted ? 'filter:blur(6px);' : ''; ?>">
                            <img src="<?php echo htmlspecialchars((string) $post['image_path']); ?>" alt="Post image">
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
                </a>

            </div>
        <?php endforeach; ?>

    </div>
</div>
</body>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
