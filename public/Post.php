<?php
session_start();
include('../database/db.php');
$userIsAdult = false;
if (isset($_SESSION['id'])) {
    $stmt = $dbconn->prepare("SELECT ålder FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $dob = new DateTime($user['ålder']);
        $today = new DateTime();
        $userIsAdult = $today->diff($dob)->y >= 18;
    }
}
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['body'])) {
    $isAdult = isset($_POST['adultcheck']) ? 1 : 0;

    $sql = "INSERT INTO posts (title, content, adultcheck) VALUES (?, ?, ?)";
    $stmt = $dbconn->prepare($sql);
    $stmt->execute([$_POST['title'], $_POST['body']]);
    header("Location: " . $_SERVER['PHP_SELF']); // reload page to show new post
    exit();
}

// Fetch all posts newest first
$posts = $dbconn->query("SELECT * FROM posts ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Home";
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="css/style.css">

<div style="width:100%; display:flex; flex-direction:column;">
    <div>
        <?php include('../includes/menu.php'); ?>
    </div>

    <div class="post-window">

        <!-- Create post form -->
        <form class="post-form" method="POST" action="">
            <input type="text" name="title" placeholder="Title" required>
            <textarea name="content" placeholder="Write something..." required></textarea>
            <button type="submit">Post</button>
            <label class="adult-toggle">
        <span>18+ content</span>
        <label class="switch">
            <input type="checkbox" name="is_adult" id="adultToggle">
            <span class="slider"></span>
        </label>
    </label>
        </form>

        <!-- Render posts from database -->
        <?php foreach ($posts as $post): ?>
            <div class="post">
                <div class="post-header">
                    <?php echo htmlspecialchars($post['title']); ?>
                </div>
                <div class="post-content">
                    <?php echo nl2br(htmlspecialchars($post['body'])); ?>
                </div>
                <div class="reply">
                    <div class="likes"></div>
                    <div class="comment"></div>
                </div>
            </div>
        <?php endforeach; ?>


    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>