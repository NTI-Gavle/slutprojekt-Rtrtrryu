<?php
$pageTitle = "Make a post";
require_once __DIR__ . '/../includes/header.php';
include('../database/db.php');

// Redirect logged-out users away immediately
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['content'])) {

   // Handle image upload
$imagePath = null;

if (isset($_FILES['postimage']) && $_FILES['postimage']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['postimage']['error'] !== UPLOAD_ERR_OK) {
        die("Upload failed. Error code: " . $_FILES['postimage']['error']);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $fileType = $finfo->file($_FILES['postimage']['tmp_name']);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$fileType])) {
        die("Only JPEG, PNG, GIF and WEBP images are allowed.");
    }

    if ($_FILES['postimage']['size'] > 5 * 1024 * 1024) {
        die("Image must be under 5MB.");
    }

    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = uniqid('post_', true) . '.' . $allowed[$fileType];
    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($_FILES['postimage']['tmp_name'], $destination)) {
        die("Could not save uploaded file.");
    }

    // Use web path
    $imagePath = 'uploads/' . $filename;
}

    $isAdult = isset($_POST['adultcheck']) ? 1 : 0;
    $sql = "INSERT INTO posts (title, body, adultcheck, creator_id, image_path, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $dbconn->prepare($sql);
    $stmt->execute([$_POST['title'], $_POST['content'], $isAdult, $_SESSION['user_id'], $imagePath]);

    // Go back to index after posting
    header("Location: index.php");
    exit();
}
?>

<link rel="stylesheet" href="css/base/style.css">

<div class="post-window">
    <form class="post-form" method="POST" action="Post.php" enctype="multipart/form-data">
        <input type="text" name="title" placeholder="Title" required>
        <textarea name="content" placeholder="Write something..." required></textarea>
        <label class="image-upload">
            <span>Add image (optional)</span>
            <input type="file" name="postimage" accept="image/*">
        </label>
        <label class="adult-toggle">
            <span>18+ content</span>
            <label class="switch">
                <input type="checkbox" name="adultcheck" id="adultToggle">
                <span class="slider"></span>
            </label>
        </label>
        <button type="submit">Post</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
