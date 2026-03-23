<?php
$pageTitle = "Home"; // <-- set dynamic page title
require_once __DIR__ . '/../includes/header.php';
?>
<?php
include('../database/db.php');

// Handle incoming post form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['content'],$_POST['user_id'])) {
        // Block logged-out users from posting
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login.php");
            exit();
        }

        $imagePath = null;
         // Handle image upload if one was provided
 if (isset($_FILES['postimage']) && $_FILES['postimage']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = mime_content_type($_FILES['postimage']['tmp_name']);

    if (!in_array($fileType, $allowed)) {
        die("Only JPEG, PNG, GIF and WEBP images are allowed.");
    }

    if ($_FILES['postimage']['size'] > 5 * 1024 * 1024) {
        die("Image must be under 5MB.");
    }

    // Give the file a unique name so uploads never overwrite each other
    $ext = pathinfo($_FILES['postimage']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('post_', true) . '.' . $ext;
    $destination = __DIR__ . '/uploads/' . $filename;

    if (move_uploaded_file($_FILES['postimage']['tmp_name'], $destination)) {
        $imagePath = 'uploads/' . $filename;
    }
}
    $isAdult = isset($_POST['adultcheck']) ? 1 : 0;
    $sql = "INSERT INTO posts (title, body, adultcheck) VALUES (?, ?, ?)";
    $stmt = $dbconn->prepare($sql);
    $stmt->execute([$_POST['title'], $_POST['content'], $isAdult]);
    header("Location: index.php");
    exit();
}



// Fetch all posts
$posts = $dbconn->query("SELECT title, body, created_at, namn FROM posts INNER JOIN användare on posts.creator_id = användare.id ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="css/style.css">
<div style="width:100%; display: flex; flex-direction:column;">
    <div width="5%">
    <?php
    include('../includes/menu.php');
    ?>
    </div>
    <div class="width:95%;">

    </div>
</div>
</div>
<button><a href="Post.php">Make post</a></button>

<div class="post-window" id="post-container">
<?php foreach ($posts as $post):?>
            <div class="post">
                <div class="post-header">
                    <span class="Post-maker">@<?php echo $post['namn'];?> --</span>
                        <?php echo htmlspecialchars($post['title']); ?>
                    
                </div>

                <div class="post-content">
                    <?php echo nl2br(htmlspecialchars($post['body'])); ?>
                </div>
                <?php if (!empty($post['image_path'])): ?>
                    <div class="post-image">
                    <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post image">
                    </div>
                    <?php var_dump($post['image_path']); ?>

                <?php endif; ?>
                        
                <div class="reply">
                    <div class="likes">
                        <i>
                            <svg xmlns="http://www.w3.org/2000/svg" width="auto" height="auto" fill="currentColor" class="bi bi-heart" viewBox="0 0 16 16">
                            <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385.92 1.815 2.834 3.989 6.286 6.357 3.452-2.368 5.365-4.542 6.286-6.357.955-1.886.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143q.09.083.176.171a3 3 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15"/>
                            </svg>
                        </i>
                    </div>
                    <div class="comment"></div>
                </div>
            </div>
        <?php endforeach; ?>
</div>

<!--<main class="main-content">

<h2>Welcome to My Home Project</h2>
<p>This is the homepage.</p>

</main>
<?php


require_once __DIR__ . "/../includes/footer.php";