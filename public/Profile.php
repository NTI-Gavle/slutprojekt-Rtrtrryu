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

$username = $profile['username'] ?? 'Unknown user';
$profileDescription = trim((string) ($profile['description'] ?? ''));
$pfpPath = $profile['avatar_path'] ?? null;
$backgroundPath = $profile['background_path'] ?? null;
?>

<link rel="stylesheet" href="css/style.css">
<style>
    .profile-page .profile-shell {
        position: relative;
        border: 4px solid #000;
        background: #e7e7e7;
        max-width: 980px;
        margin: 0 auto;
    }

    .profile-page .profile-top-banner {
        height: 150px;
        border-bottom: 4px solid #000;
        background: #ddd;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: #1f1f1f;
        overflow: hidden;
    }

    .profile-page .profile-banner-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .profile-page .profile-avatar-wrap {
        position: absolute;
        top: 70px;
        right: 22px;
        z-index: 2;
    }

    .profile-page .profile-avatar {
        width: 175px;
        height: 175px;
        border-radius: 50%;
        border: 4px solid #000;
        background: #000;
        object-fit: cover;
        display: block;
    }

    .profile-page .profile-content {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 28px;
        padding: 52px 34px 26px;
    }

    .profile-page .panel-frame {
        border: 4px solid #000;
        background: #efefef;
        padding: 14px;
    }

    .profile-page .profile-liked {
        min-height: 300px;
        max-height: 300px;
        display: flex;
        flex-direction: column;
    }

    .profile-page .liked-posts-scroll {
        overflow-y: auto;
    }

    .profile-page .liked-posts-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: 8px;
    }

    .profile-page .liked-post-item {
        border: 2px solid #000;
        background: #fafafa;
    }

    .profile-page .liked-post-link {
        display: block;
        color: #111;
        text-decoration: none;
        padding: 8px;
    }

    .profile-page .liked-post-link:hover {
        background: #ececec;
    }

    .profile-page .liked-post-meta,
    .profile-page .liked-post-body {
        display: block;
        font-size: 0.82rem;
        color: #353535;
    }

    .profile-page .profile-description {
        min-height: 300px;
    }

    .profile-page .profile-edit-form {
        display: grid;
        gap: 8px;
    }

    .profile-page .profile-edit-form textarea,
    .profile-page .profile-edit-form input {
        width: 100%;
        border: 2px solid #000;
        background: #f8f8f8;
        padding: 8px;
    }

    .profile-page .profile-save-btn {
        border: 2px solid #000;
        background: #111;
        color: #fff;
        padding: 8px 10px;
        cursor: pointer;
    }

    .profile-page .profile-feedback {
        margin: 0 0 8px;
        padding: 6px 8px;
        border: 2px solid #000;
        font-size: 0.85rem;
    }

    .profile-page .profile-feedback.ok { background: #d7ffd5; }
    .profile-page .profile-feedback.error { background: #ffd8d8; }

    @media (max-width: 992px) {
        .profile-page .profile-avatar-wrap {
            right: 12px;
            top: 85px;
        }

        .profile-page .profile-avatar {
            width: 128px;
            height: 128px;
        }

        .profile-page .profile-content {
            grid-template-columns: 1fr;
            gap: 14px;
            padding: 48px 12px 14px;
        }

        .profile-page .profile-liked,
        .profile-page .profile-description {
            min-height: 170px;
            max-height: none;
        }
    }
</style>

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
            <section class="profile-liked panel-frame">
                <h5 class="mb-3">Liked post</h5>
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

            <aside class="profile-description panel-frame">
                <h5 class="mb-3"><?php echo htmlspecialchars($username); ?></h5>
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
                        <label for="profile_description" class="profile-label">Description</label>
                        <textarea id="profile_description" name="profile_description" rows="5" maxlength="2000" placeholder="Write your profile description..."><?php echo htmlspecialchars($profileDescription); ?></textarea>

                        <label for="avatar_file" class="profile-label">Profile image</label>
                        <input id="avatar_file" name="avatar_file" type="file" accept="image/*">
                        <input type="text" value="<?php echo htmlspecialchars((string) ($pfpPath ?? '')); ?>" readonly>

                        <label for="background_file" class="profile-label">Background image</label>
                        <input id="background_file" name="background_file" type="file" accept="image/*">
                        <input type="text" value="<?php echo htmlspecialchars((string) ($backgroundPath ?? '')); ?>" readonly>

                        <button type="submit" class="profile-save-btn">Save profile</button>
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
