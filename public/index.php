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
<body>
    

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
                <a href="PostViewer.php?post_id=<?php echo($post['id']); ?>" class="text-decoration-none link-dark">
                <div class="post-header" style="<?php echo $restricted ? 'filter:blur(6px);' : ''; ?>">
                    <span class="post-author">@<?php echo htmlspecialchars($post['username']); ?></span><br>
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
        </a>
        <?php endforeach; ?>

    </div>
</div>
</body>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>