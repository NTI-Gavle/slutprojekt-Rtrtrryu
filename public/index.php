<?php
$pageTitle = "Home"; // <-- set dynamic page title
require_once __DIR__ . '/../includes/header.php';
?>
<?php
include('../database/db.php');

// Handle incoming post form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['content'])) {
        // Block logged-out users from posting
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login.php");
            exit();
        }
    
    $isAdult = isset($_POST['adultcheck']) ? 1 : 0;
    $sql = "INSERT INTO posts (title, body, adultcheck) VALUES (?, ?, ?)";
    $stmt = $dbconn->prepare($sql);
    $stmt->execute([$_POST['title'], $_POST['content'], $isAdult]);
    header("Location: index.php");
    exit();
}

// Check if logged-in user is 18+
$userIsAdult = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $dbconn->prepare("SELECT ålder FROM användare WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $dob = new DateTime($user['ålder']);
        $today = new DateTime();
        $userIsAdult = $today->diff($dob)->y >= 18;
    }
}

// Fetch all posts
$posts = $dbconn->query("SELECT * FROM posts ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
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

<!--<main class="main-content">

<h2>Welcome to My Home Project</h2>
<p>This is the homepage.</p>

</main>
<?php
require_once __DIR__ . '/../includes/footer.php';