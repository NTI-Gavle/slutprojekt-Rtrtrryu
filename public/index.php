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

<div style="width:100%; display:flex; flex-direction:column;">
    <div><?php include('../includes/menu.php'); ?></div>

    <div class="post-window">

        <?php if ($currentUserId > 0): ?>
            <a href="Post.php"><button>Make a post</button></a>
        <?php else: ?>
            <div class="login-prompt">
                <a href="login.php">Log in to make a post</a>
            </div>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
            <p class="admin-mode-note">Admin mode active: you can delete posts.</p>
        <?php endif; ?>

        <?php if ($adminMessage !== null): ?>
            <p class="admin-mode-feedback"><?php echo htmlspecialchars($adminMessage); ?></p>
        <?php endif; ?>

        <?php foreach ($posts as $post): ?>
            <?php $restricted = !empty($post['adultcheck']) && ($currentUserId <= 0 || !$userIsAdult); ?>
            <?php $canDeletePost = $currentUserId > 0 && ($isAdmin || (int) ($post['creator_id'] ?? 0) === $currentUserId); ?>
            <div class="post" style="position:relative;">
                <?php if ($canDeletePost): ?>
                    <form method="POST" action="index.php" class="admin-delete-form" onsubmit="return confirm('Delete this post?');">
                        <input type="hidden" name="delete_post_id" value="<?php echo (int) $post['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $_SESSION['csrf_token']); ?>">
                        <button type="submit" class="admin-delete-btn">Delete</button>
                    </form>
                <?php endif; ?>

                <a href="PostViewer.php?post_id=<?php echo (int) $post['id']; ?>" class="text-decoration-none link-dark d-block">
                    <div class="post-header" style="<?php echo $restricted ? 'filter:blur(60px);' : ''; ?>">
                        <span class="post-author">@<?php echo htmlspecialchars((string) ($post['username'] ?? 'unknown')); ?></span><br>
                        <span class="post-title"><?php echo htmlspecialchars((string) ($post['title'] ?? 'Untitled')); ?></span>
                        <?php if (!empty($post['adultcheck'])): ?>
                            <span class="adult-badge">18+</span>
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

                    <div class="reply">
                        <div class="comment"></div>
                    </div>

                    <?php if ($restricted): ?>
                        <div class="adult-overlay">
                            <p>This post is 18+ only</p>
                            <?php if ($currentUserId <= 0): ?>
                                <a href="login.php">Log in to view</a>
                            <?php else: ?>
                                <p>You must be 18 or older to view this</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </a>

                <?php if ($canDeletePost): ?>
                    <div class="admin-post-id">Post #<?php echo (int) $post['id']; ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

    </div>
</div>
</body>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
