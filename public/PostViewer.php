<?php
require_once __DIR__ . '/../includes/header.php';
include('../database/db.php');
require_once __DIR__ . '/../database/user_queries.php';

$userIsAdult = false;
if (isset($_SESSION['user_id'])) {
    $userIsAdult = getUserAge($dbconn, (int) $_SESSION['user_id']) >= 18;
}

$postId = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT);
if (!$postId) {
    die('No valid post selected.');
}

$post = null;
$postsTable = resolveTableName($dbconn, ['posts', 'post']);
$userMeta = getUserTableMeta($dbconn);

if ($postsTable !== null && $userMeta !== null) {
    $postColumns = getTableColumns($dbconn, $postsTable);
    $postIdColumn = findColumn($postColumns, ['id', 'post_id']);
    $postCreatorColumn = findColumn($postColumns, ['creator_id', 'user_id', 'author_id']);

    if ($postIdColumn !== null && $postCreatorColumn !== null) {
        $avatarSelect = $userMeta['avatar_column'] !== null
            ? "u.`{$userMeta['avatar_column']}` AS avatar_path"
            : "NULL AS avatar_path";

        $sql = "
            SELECT p.*, u.`{$userMeta['name_column']}` AS username, {$avatarSelect}
            FROM `{$postsTable}` p
            JOIN `{$userMeta['table']}` u ON p.`{$postCreatorColumn}` = u.`{$userMeta['id_column']}`
            WHERE p.`{$postIdColumn}` = ?
            LIMIT 1
        ";

        $stmt = $dbconn->prepare($sql);
        $stmt->execute([$postId]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!$post) {
    die('Post not found.');
}

$restricted = !empty($post['adultcheck']) && (!isset($_SESSION['user_id']) || !$userIsAdult);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Viewer</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/base/style.css">
    <link rel="stylesheet" href="css/pages/postviewer.css">
    <script src="js/app.js" defer></script>
</head>
<body onload="RefreshLikes(<?php echo (int)$postId; ?>)" style="background-color: darkmagenta">

<div class="container py-4" style="background-color: darkviolet;">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm position-relative">
                <div class="card-body">
                    <div class="<?php echo $restricted ? 'blurred' : ''; ?>">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <?php if (!empty($post['avatar_path'])): ?>
                                <img src="<?php echo htmlspecialchars((string) $post['avatar_path']); ?>" alt="Profile picture" class="post-author-avatar">
                            <?php else: ?>
                                <div class="post-author-avatar post-author-avatar-fallback">Pfp</div>
                            <?php endif; ?>
                            <a href="Profile.php?user_id=<?php echo (int) ($post['creator_id'] ?? 0); ?>" class="text-muted text-decoration-none">
                                @<?php echo htmlspecialchars((string) ($post['username'] ?? 'unknown')); ?>
                            </a>
                            <?php if (!empty($post['adultcheck'])): ?>
                                <span class="badge text-bg-danger">18+</span>
                            <?php endif; ?>
                        </div>

                        <h5 class="card-title fw-bold mb-3">
                            <?php echo htmlspecialchars((string) ($post['title'] ?? 'Untitled')); ?>
                        </h5>

                        <div class="row g-3 align-items-start mb-3">
                            <div class="<?php echo !empty($post['image_path']) ? 'col-md-8' : 'col-12'; ?>">
                                <p class="card-text mb-0 border p-2 rounded">
                                    <?php echo nl2br(htmlspecialchars((string) ($post['body'] ?? ''))); ?>
                                </p>
                            </div>

                            <?php if (!empty($post['image_path'])): ?>
                                <div class="col-md-3">
                                    <img
                                        src="<?php echo htmlspecialchars((string) $post['image_path']); ?>"
                                        alt="Post image"
                                        class="img-fluid rounded post-image-preview"
                                        onclick="openLightbox(this.src)"
                                    >
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="likebox">
                        <p id="like_counter" class="counter"></p>
                        <button
                            type="button"
                            id="like"
                            class="btn btn-outline-danger"
                            onclick="Like(<?php echo (int)$postId; ?>)">
                        </button>
                    </div>
                </div>

                <?php if ($restricted): ?>
                    <div class="adult-overlay">
                        <p class="fw-bold mb-2">This post is 18+ only</p>
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <a href="login.php">Log in to view</a>
                        <?php else: ?>
                            <p class="mb-0">You must be 18 or older to view this.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php include __DIR__ . '/../includes/post-comments.php'; ?>

        </div>
    </div>
</div>

<div id="imageLightbox" class="image-lightbox" onclick="closeLightbox()">
    <img id="lightboxImg" src="" alt="Zoomed image">
</div>

</body>
</html>


