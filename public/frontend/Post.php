<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../database/user_queries.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

function savePostImageUpload(string $fieldName, ?string &$errorMessage): ?string
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = 'Image upload failed. Please try again.';
        return null;
    }

    if ($_FILES[$fieldName]['size'] > 5 * 1024 * 1024) {
        $errorMessage = 'Image must be under 5MB.';
        return null;
    }

    $tmpPath = $_FILES[$fieldName]['tmp_name'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($tmpPath);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mimeType])) {
        $errorMessage = 'Only JPEG, PNG, GIF and WEBP images are allowed.';
        return null;
    }

    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = uniqid('post_', true) . '.' . $allowed[$mimeType];
    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($tmpPath, $destination)) {
        $errorMessage = 'Could not save uploaded file.';
        return null;
    }

    return 'uploads/' . $filename;
}

function postPreviewPlaceholderDataUri(): string
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 520"><rect width="1000" height="520" fill="#eef2f7"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#64748b" font-family="Arial, sans-serif" font-size="34">Image preview</text></svg>';
    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

$currentUserId = (int) $_SESSION['user_id'];
$editingPostId = filter_input(INPUT_GET, 'edit_post_id', FILTER_VALIDATE_INT);
$editingPost = null;

if ($editingPostId) {
    $editingPost = getPostById($dbconn, (int) $editingPostId);
    if ($editingPost === null || !canUserEditPost($dbconn, $currentUserId, (int) $editingPostId)) {
        http_response_code(403);
        echo '<div class="container py-4"><div class="alert alert-danger">You cannot edit this post.</div></div>';
        require_once __DIR__ . '/../../includes/footer.php';
        exit();
    }
}

$isEditMode = $editingPost !== null;
$pageTitle = $isEditMode ? 'Edit post' : 'Make a post';
$bodyStyle = 'background-color: darkmagenta';
require_once __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../database/db.php';

$saveError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['content'])) {
    $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    $title = trim((string) $_POST['title']);
    $content = trim((string) $_POST['content']);
    $isAdult = isset($_POST['adultcheck']) ? 1 : 0;

    if ($title === '' || $content === '') {
        $saveError = 'Title and content are required.';
    } else {
        $imagePath = savePostImageUpload('postimage', $saveError);

        if ($saveError === null && $postId) {
            if (!canUserEditPost($dbconn, $currentUserId, (int) $postId)) {
                $saveError = 'You cannot edit this post.';
            } elseif (updatePostForUser($dbconn, $currentUserId, (int) $postId, $title, $content, $isAdult, $imagePath)) {
                header('Location: PostViewer.php?post_id=' . (int) $postId);
                exit();
            } else {
                $saveError = 'Could not update the post right now.';
            }
        } elseif ($saveError === null) {
            $postsTable = resolveTableName($dbconn, ['posts', 'post']);
            if ($postsTable === null) {
                $saveError = 'Could not find the posts table.';
            } else {
                $sql = "INSERT INTO `{$postsTable}` (title, body, adultcheck, creator_id, image_path, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
                $stmt = $dbconn->prepare($sql);
                $saved = $stmt->execute([$title, $content, $isAdult, $currentUserId, $imagePath]);

                if ($saved) {
                    header('Location: index.php');
                    exit();
                }

                $saveError = 'Could not save the post right now.';
            }
        }
    }
}

$formTitle = '';
$formContent = '';
$formAdult = false;
$formImagePath = null;
$formPostId = $isEditMode ? (int) ($editingPost['id'] ?? 0) : 0;
$previewImageSrc = postPreviewPlaceholderDataUri();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formTitle = (string) ($_POST['title'] ?? '');
    $formContent = (string) ($_POST['content'] ?? '');
    $formAdult = isset($_POST['adultcheck']);
} elseif ($isEditMode) {
    $formTitle = (string) ($editingPost['title'] ?? '');
    $formContent = (string) ($editingPost['body'] ?? '');
    $formAdult = !empty($editingPost['adultcheck']);
    $formImagePath = !empty($editingPost['image_path']) ? (string) $editingPost['image_path'] : null;
}

if ($formImagePath !== null) {
    $previewImageSrc = site_asset_url($formImagePath);
}
?>

<div class="post-window" style="background-color: darkviolet;">
    <?php if ($saveError !== null): ?>
        <div class="alert alert-danger mb-3"><?php echo htmlspecialchars($saveError); ?></div>
    <?php endif; ?>

    <form class="post-form" method="POST" action="Post.php<?php echo $isEditMode ? '?edit_post_id=' . (int) $formPostId : ''; ?>" enctype="multipart/form-data">
        <?php if ($isEditMode): ?>
            <input type="hidden" name="post_id" value="<?php echo (int) $formPostId; ?>">
        <?php endif; ?>

        <input type="text" name="title" placeholder="Title" required value="<?php echo htmlspecialchars($formTitle); ?>" maxlength="120">
        <textarea name="content" placeholder="Write something..." required maxlength="5000"><?php echo htmlspecialchars($formContent); ?></textarea>

        <div class="mb-3">
            <img src="<?php echo htmlspecialchars($previewImageSrc); ?>" alt="Post image preview" class="img-fluid rounded post-edit-preview">
        </div>

        <label for="postimage" class="form-label image-upload-label mb-1">
            <?php echo $isEditMode ? 'Replace image (optional)' : 'Add image (optional)'; ?>
        </label>
        <input type="file" id="postimage" name="postimage" accept="image/*" class="form-control form-control-sm post-image-input" data-preview-target=".post-edit-preview">

        <label class="adult-toggle">
            <span>18+ content</span>
            <label class="switch">
                <input type="checkbox" name="adultcheck" id="adultToggle" <?php echo $formAdult ? 'checked' : ''; ?>>
                <span class="slider"></span>
            </label>
        </label>

        <div class="d-flex gap-2 align-items-center">
            <button type="submit"><?php echo $isEditMode ? 'Save changes' : 'Post'; ?></button>
            <?php if ($isEditMode): ?>
                <a href="PostViewer.php?post_id=<?php echo (int) $formPostId; ?>" class="btn btn-outline-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
