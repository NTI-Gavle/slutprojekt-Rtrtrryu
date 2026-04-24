<?php
$pageTitle = "Profile";
require_once __DIR__ . '/../includes/header.php';
include('../database/db.php');
require_once __DIR__ . '/../database/user_queries.php';

function saveProfileImageUpload(string $fieldName, string $prefix, ?string &$errorMessage): ?string
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

    $uploadDir = __DIR__ . '/uploads/profile/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = uniqid($prefix . '_', true) . '.' . $allowed[$mimeType];
    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($tmpPath, $destination)) {
        $errorMessage = 'Could not save uploaded image.';
        return null;
    }

    return 'uploads/profile/' . $filename;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$profileUserId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
if (!$profileUserId) {
    $profileUserId = (int) $_SESSION['user_id'];
}

$profile = getUserProfileData($dbconn, (int) $profileUserId);
if ($profile === null) {
    http_response_code(404);
    echo '<div class="container py-4"><p>Profile not found.</p></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

$isOwnProfile = (int) $_SESSION['user_id'] === (int) $profileUserId;
$saveMessage = null;
$saveError = null;

if ($isOwnProfile && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $newDescription = trim((string) ($_POST['profile_description'] ?? ''));
    $newAvatarPath = trim((string) ($profile['avatar_path'] ?? ''));
    $newBackgroundPath = trim((string) ($profile['background_path'] ?? ''));

    $uploadedAvatarPath = saveProfileImageUpload('avatar_file', 'avatar', $saveError);
    if ($uploadedAvatarPath !== null) {
        $newAvatarPath = $uploadedAvatarPath;
    }

    $uploadedBackgroundPath = saveProfileImageUpload('background_file', 'background', $saveError);
    if ($uploadedBackgroundPath !== null) {
        $newBackgroundPath = $uploadedBackgroundPath;
    }

    if ($saveError === null && mb_strlen($newDescription) > 2000) {
        $saveError = 'Profile description is too long (max 2000 characters).';
    } elseif ($saveError === null && (mb_strlen($newAvatarPath) > 255 || mb_strlen($newBackgroundPath) > 255)) {
        $saveError = 'Image path is too long (max 255 characters).';
    }

    if ($saveError === null) {
        try {
            $saved = updateUserProfileData(
                $dbconn,
                (int) $_SESSION['user_id'],
                $newDescription,
                $newAvatarPath,
                $newBackgroundPath
            );

            if ($saved) {
                $saveMessage = 'Profile updated successfully.';
                $_SESSION['avatar_path'] = $newAvatarPath;
            } else {
                $saveError = 'Could not save profile right now.';
            }
        } catch (Throwable $e) {
            $saveError = 'Could not save profile right now. Please try a simpler text or another image.';
        }
    }
}

$profile = getUserProfileData($dbconn, (int) $profileUserId);
$likedPosts = getUserLikedPosts($dbconn, (int) $profileUserId, 30);
$madePosts = getUserCreatedPosts($dbconn, (int) $profileUserId, 30);

$username = $profile['username'] ?? 'Unknown user';
$profileDescription = trim((string) ($profile['description'] ?? ''));
$pfpPath = $profile['avatar_path'] ?? null;
$backgroundPath = $profile['background_path'] ?? null;
?>

<link rel="stylesheet" href="css/base/style.css">
<link rel="stylesheet" href="css/pages/profile.css">
<div class="container py-4 profile-page">
    <div class="profile-shell">
        <div class="profile-top-banner">
            <?php if (!empty($backgroundPath)): ?>
                <img src="<?php echo htmlspecialchars((string) $backgroundPath); ?>" alt="Background image" class="profile-banner-image">
            <?php else: ?>
                <span>Background image</span>
            <?php endif; ?>
        </div>

        <div class="profile-avatar-wrap">
            <?php if (!empty($pfpPath)): ?>
                <img src="<?php echo htmlspecialchars($pfpPath); ?>" alt="Profile picture" class="profile-avatar">
            <?php else: ?>
                <div class="profile-avatar d-flex align-items-center justify-content-center text-white">Pfp</div>
            <?php endif; ?>
        </div>

        <div class="profile-content">
            <div class="profile-left-stack">
                <section class="profile-liked panel-frame shadow-sm">
                    <h5 class="mb-3 fw-semibold">Liked posts</h5>
                    <?php if (empty($likedPosts)): ?>
                        <p class="text-muted mb-0">No liked posts yet.</p>
                    <?php else: ?>
                        <div class="liked-posts-scroll">
                            <ul class="liked-posts-list">
                                <?php foreach ($likedPosts as $post): ?>
                                    <li class="liked-post-item">
                                        <a href="PostViewer.php?post_id=<?php echo (int) $post['post_id']; ?>" class="liked-post-link">
                                            <strong><?php echo htmlspecialchars((string) ($post['title'] ?? 'Untitled')); ?></strong>
                                            <?php if (!empty($post['author_name'])): ?>
                                                <span class="liked-post-meta">@<?php echo htmlspecialchars((string) $post['author_name']); ?></span>
                                            <?php endif; ?>
                                            <span class="liked-post-body">
                                                <?php
                                                    $body = trim((string) ($post['body'] ?? ''));
                                                    echo htmlspecialchars(mb_strlen($body) > 70 ? mb_substr($body, 0, 70) . '...' : $body);
                                                ?>
                                            </span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="profile-made panel-frame shadow-sm">
                    <h5 class="mb-3 fw-semibold">Made posts</h5>
                    <?php if (empty($madePosts)): ?>
                        <p class="text-muted mb-0">No posts yet.</p>
                    <?php else: ?>
                        <div class="liked-posts-scroll">
                            <ul class="liked-posts-list">
                                <?php foreach ($madePosts as $post): ?>
                                    <li class="liked-post-item">
                                        <a href="PostViewer.php?post_id=<?php echo (int) $post['post_id']; ?>" class="liked-post-link">
                                            <strong><?php echo htmlspecialchars((string) ($post['title'] ?? 'Untitled')); ?></strong>
                                            <span class="liked-post-body">
                                                <?php
                                                    $body = trim((string) ($post['body'] ?? ''));
                                                    echo htmlspecialchars(mb_strlen($body) > 70 ? mb_substr($body, 0, 70) . '...' : $body);
                                                ?>
                                            </span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <aside class="profile-description panel-frame shadow-sm">
                <h5 class="mb-3 fw-semibold"><?php echo htmlspecialchars($username); ?></h5>
                <?php if ($isOwnProfile): ?>
                    <p class="profile-subtitle">Edit your profile</p>
                <?php endif; ?>

                <?php if ($isOwnProfile): ?>
                    <?php if ($saveMessage !== null): ?>
                        <p class="profile-feedback ok"><?php echo htmlspecialchars($saveMessage); ?></p>
                    <?php endif; ?>
                    <?php if ($saveError !== null): ?>
                        <p class="profile-feedback error"><?php echo htmlspecialchars($saveError); ?></p>
                    <?php endif; ?>

                    <form method="POST" action="Profile.php" class="profile-edit-form" enctype="multipart/form-data">
                        <label for="profile_description" class="form-label profile-label mb-0">Description</label>
                        <textarea id="profile_description" class="form-control" name="profile_description" rows="5" maxlength="2000" placeholder="Write your profile description..."><?php echo htmlspecialchars($profileDescription); ?></textarea>

                        <label for="avatar_file" class="form-label profile-label mb-0 mt-1">Profile image</label>
                        <input id="avatar_file" class="form-control" name="avatar_file" type="file" accept="image/jpeg, image/png, image/gif, image/webp">
                        <input class="form-control form-control-sm text-muted" type="text" value="<?php echo htmlspecialchars((string) ($pfpPath ?? '')); ?>" readonly>

                        <label for="background_file" class="form-label profile-label mb-0 mt-1">Background image</label>
                        <input id="background_file" class="form-control" name="background_file" type="file" accept="image/*">
                        <input class="form-control form-control-sm text-muted" type="text" value="<?php echo htmlspecialchars((string) ($backgroundPath ?? '')); ?>" readonly>

                        <button type="submit" class="btn btn-dark mt-2">Save profile</button>
                    </form>
                <?php else: ?>
                    <?php if ($profileDescription !== ''): ?>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($profileDescription)); ?></p>
                    <?php else: ?>
                        <p class="mb-0 text-muted">No profile description added yet.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


