<?php
include('../database/db.php');
// Fetch all posts newest first
$posts = $dbconn->query("SELECT * FROM posts ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Home";
require_once __DIR__ . '/../includes/header.php';


$userIsAdult = false;
if (isset($_SESSION['id'])) {
    $stmt = $dbconn->prepare("SELECT ålder FROM user WHERE id = ?");
    $stmt->execute([$_SESSION['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $dob = new DateTime($user['ålder']);
        $today = new DateTime();
        $userIsAdult = $today->diff($dob)->y >= 18;
    }
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['content'])) {
    $isAdult = isset($_POST['adultcheck']) ? 1 : 0;
    $sql = "INSERT INTO posts (title, body, adultcheck, creator_id) VALUES (?, ?, ?, ?)";
    $stmt = $dbconn->prepare($sql);
    $stmt->execute([$_POST['title'], $_POST['content'], $isAdult, $_SESSION['user_id']]);
    header("Location: index.php"); // reload page to show new post
    exit();
}


?>

<link rel="stylesheet" href="css/style.css">

<div style="width:100%; display:flex; flex-direction:column;">
    <div>
        <?php include('../includes/menu.php'); ?>
    </div>

    <div class="post-window">

        <!-- Create post form -->
        <form class="post-form" method="POST" action="" enctype="multipart/form-data">
            <input type="text" name="title" placeholder="Title" required>
            <textarea name="content" placeholder="Write something..." required></textarea>
            <label class="image-upload">
                <span>Add image (optional)</span>
                <input type="file" name="postimage" accept="image/*">
            </label>
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
        


    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>