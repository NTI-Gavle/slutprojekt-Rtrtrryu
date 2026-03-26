<?php
$pageTitle = "Home";
require_once __DIR__ . '/../includes/header.php';
include('../database/db.php');

// Check adult status
$userIsAdult = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $dbconn->prepare("SELECT ålder FROM användare WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $userIsAdult = (int)$user['ålder'] >= 18;
    }
}

// Fetch posts with username
$posts = $dbconn->query("
    SELECT posts.*, `användare`.`namn` AS username
    FROM posts
    JOIN `användare` ON posts.creator_id = `användare`.id
    ORDER BY posts.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="css/style.css">

<div style="width:100%; display:flex; flex-direction:column;">
    <div><?php include('../includes/menu.php'); ?></div>

    <div class="post-window">

        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="Post.php"><button>Make a post</button></a>
        <?php else: ?>
            <div class="login-prompt">
                <a href="login.php">Log in to make a post</a>
            </div>
        <?php endif; ?>

        <?php foreach ($posts as $post): ?>
            <?php $restricted = $post['adultcheck'] && (!isset($_SESSION['user_id']) || !$userIsAdult); ?>
            <div class="post" style="position:relative;">
                <div class="post-header" style="<?php echo $restricted ? 'filter:blur(6px);' : ''; ?>">
                    <span class="post-author">@<?php echo htmlspecialchars($post['username']); ?></span>
                    <span class="post-title"><?php echo htmlspecialchars($post['title']); ?></span>
                    <?php if ($post['adultcheck']): ?>
                        <span class="adult-badge">18+</span>
                    <?php endif; ?>
                </div>
                <div class="post-content" style="<?php echo $restricted ? 'filter:blur(6px);user-select:none;' : ''; ?>">
                    <?php echo nl2br(htmlspecialchars($post['body'])); ?>
                </div>
                <?php if (!empty($post['image_path'])): ?>
                    <div class="post-image" style="<?php echo $restricted ? 'filter:blur(6px);' : ''; ?>">
                        <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post image">
                    </div>
                <?php endif; ?>
                <div class="reply">
                    <div class="likes">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                            <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385.92 1.815 2.834 3.989 6.286 6.357 3.452-2.368 5.365-4.542 6.286-6.357.955-1.886.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143q.09.083.176.171a3 3 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15"/>
                        </svg>
                    </div>
                    <div class="comment"></div>
                </div>
                <?php if ($restricted): ?>
                    <div class="adult-overlay">
                        <p>🔞 This post is 18+ only</p>
                        <?php if (!isset($_SESSION['user_id'])): ?>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>