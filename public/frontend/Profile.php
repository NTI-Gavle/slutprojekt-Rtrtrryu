<?php
$pageTitle = "Profile";
$bodyStyle = 'background-color: darkmagenta';
$extraStyles = ['css/pages/profile.css'];
require_once __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../database/user_queries.php';

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

    $uploadDir = __DIR__ . '/../uploads/profile/';
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

function profilePlaceholderDataUri(string $label, string $background = '#e8ecf1', string $foreground = '#374151'): string
{
    $svg = sprintf(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 400"><rect width="1200" height="400" fill="%s"/><text x="50%%" y="50%%" dominant-baseline="middle" text-anchor="middle" fill="%s" font-family="Arial, sans-serif" font-size="44">%s</text></svg>',
        htmlspecialchars($background, ENT_QUOTES),
        htmlspecialchars($foreground, ENT_QUOTES),
        htmlspecialchars($label, ENT_QUOTES)
    );

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

function normalizeFitValue(?string $value, string $fallback = 'cover'): string
{
    $allowed = ['contain', 'stretch'];
    $value = trim((string) $value);
    return in_array($value, $allowed, true) ? $value : $fallback;
}

function profileFitCssValue(string $fit): string
{
    return $fit === 'stretch' ? 'fill' : $fit;
}

function clampPercentValue($value, int $fallback = 50): int
{
    if (!is_numeric($value)) {
        return $fallback;
    }

    return max(0, min(100, (int) $value));
}

function clampScaleValue($value, int $fallback = 100): int
{
    if (!is_numeric($value)) {
        return $fallback;
    }

    return max(100, min(200, (int) $value));
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
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

$isOwnProfile = (int) $_SESSION['user_id'] === (int) $profileUserId;
$saveMessage = null;
$saveError = null;

if ($isOwnProfile && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $newDescription = trim((string) ($_POST['profile_description'] ?? ''));
    $newAvatarPath = trim((string) ($profile['avatar_path'] ?? ''));
    $newBackgroundPath = trim((string) ($profile['background_path'] ?? ''));
    $avatarFit = normalizeFitValue($_POST['avatar_fit'] ?? ($profile['avatar_fit'] ?? 'contain'));
    $avatarPosX = clampPercentValue($_POST['avatar_pos_x'] ?? ($profile['avatar_pos_x'] ?? 50));
    $avatarPosY = clampPercentValue($_POST['avatar_pos_y'] ?? ($profile['avatar_pos_y'] ?? 50));
    $avatarScale = clampScaleValue($_POST['avatar_scale'] ?? ($profile['avatar_scale'] ?? 100));
    $backgroundFit = normalizeFitValue($_POST['background_fit'] ?? ($profile['background_fit'] ?? 'contain'));
    $backgroundPosX = clampPercentValue($_POST['background_pos_x'] ?? ($profile['background_pos_x'] ?? 50));
    $backgroundPosY = clampPercentValue($_POST['background_pos_y'] ?? ($profile['background_pos_y'] ?? 50));
    $backgroundScale = clampScaleValue($_POST['background_scale'] ?? ($profile['background_scale'] ?? 100));

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
                $newBackgroundPath,
                $avatarFit,
                $avatarPosX,
                $avatarPosY,
                $avatarScale,
                $backgroundFit,
                $backgroundPosX,
                $backgroundPosY,
                $backgroundScale
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
$avatarFit = normalizeFitValue($profile['avatar_fit'] ?? 'contain');
$avatarPosX = clampPercentValue($profile['avatar_pos_x'] ?? 50);
$avatarPosY = clampPercentValue($profile['avatar_pos_y'] ?? 50);
$avatarScale = clampScaleValue($profile['avatar_scale'] ?? 100);
$backgroundFit = normalizeFitValue($profile['background_fit'] ?? 'contain');
$backgroundPosX = clampPercentValue($profile['background_pos_x'] ?? 50);
$backgroundPosY = clampPercentValue($profile['background_pos_y'] ?? 50);
$backgroundScale = clampScaleValue($profile['background_scale'] ?? 100);
$profileAvatarSrc = !empty($pfpPath) ? site_asset_url((string) $pfpPath) : profilePlaceholderDataUri('Pfp', '#111111', '#ffffff');
$profileBackgroundSrc = !empty($backgroundPath) ? site_asset_url((string) $backgroundPath) : profilePlaceholderDataUri('Background image');
?>
    

<div class="container py-4 profile-page" style="background-color: darkviolet;">
    <div class="profile-shell">
        <div class="profile-top-banner">
            <div class="profile-banner-frame">
                <img
                    id="profile-background-preview"
                    src="<?php echo htmlspecialchars($profileBackgroundSrc); ?>"
                    alt="Background image"
                    class="profile-banner-image"
                    style="object-fit: <?php echo htmlspecialchars(profileFitCssValue($backgroundFit)); ?>; transform: translate(<?php echo ((int) $backgroundPosX - 50); ?>%, <?php echo ((int) $backgroundPosY - 50); ?>%) scale(<?php echo ((int) $backgroundScale) / 100; ?>); transform-origin: center center;"
                    data-profile-object-position-x="<?php echo (int) $backgroundPosX; ?>"
                    data-profile-object-position-y="<?php echo (int) $backgroundPosY; ?>"
                    data-profile-scale="<?php echo (int) $backgroundScale; ?>"
                >
            </div>
            <?php if ($isOwnProfile): ?>
                <button type="button" class="btn btn-sm btn-dark profile-media-btn profile-banner-btn" data-action="toggle-profile-editor" data-target="background-editor">Edit background</button>
                <div id="background-editor" class="profile-editor-popover d-none" aria-label="Background editor">
                    <div class="profile-editor-head">
                        <strong>Background mode</strong>
                        <button type="button" class="profile-editor-close" data-action="toggle-profile-editor" data-target="background-editor">&times;</button>
                    </div>
                    <label class="profile-editor-label">Fit
                        <select class="form-select form-select-sm" name="background_fit" form="profile-edit-form" data-preview-target="#profile-background-preview" data-preview-style="objectFit">
                            <?php foreach (['contain', 'stretch'] as $fitOption): ?>
                                <option value="<?php echo htmlspecialchars($fitOption); ?>" <?php echo $backgroundFit === $fitOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($fitOption); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="profile-editor-label">Size
                        <input type="range" min="100" max="200" name="background_scale" form="profile-edit-form" value="<?php echo (int) $backgroundScale; ?>" data-preview-target="#profile-background-preview" data-preview-style="scale">
                    </label>
                    <label class="profile-editor-label">Horizontal position
                        <input type="range" min="0" max="100" name="background_pos_x" form="profile-edit-form" value="<?php echo (int) $backgroundPosX; ?>" data-preview-target="#profile-background-preview" data-preview-style="objectPositionX">
                    </label>
                    <label class="profile-editor-label">Vertical position
                        <input type="range" min="0" max="100" name="background_pos_y" form="profile-edit-form" value="<?php echo (int) $backgroundPosY; ?>" data-preview-target="#profile-background-preview" data-preview-style="objectPositionY">
                    </label>
                    <label for="background_file" class="btn btn-sm btn-outline-light profile-editor-upload">Replace image</label>
                </div>
            <?php endif; ?>
        </div>

        <div class="profile-avatar-wrap">
            <div class="profile-avatar-frame">
                <img
                    id="profile-avatar-preview"
                    src="<?php echo htmlspecialchars($profileAvatarSrc); ?>"
                    alt="Profile picture"
                    class="profile-avatar"
                    style="object-fit: <?php echo htmlspecialchars(profileFitCssValue($avatarFit)); ?>; transform: translate(<?php echo ((int) $avatarPosX - 50); ?>%, <?php echo ((int) $avatarPosY - 50); ?>%) scale(<?php echo ((int) $avatarScale) / 100; ?>); transform-origin: center center;"
                    data-profile-object-position-x="<?php echo (int) $avatarPosX; ?>"
                    data-profile-object-position-y="<?php echo (int) $avatarPosY; ?>"
                    data-profile-scale="<?php echo (int) $avatarScale; ?>"
                >
            </div>
            <?php if ($isOwnProfile): ?>
                <button type="button" class="btn btn-sm btn-dark profile-media-btn profile-avatar-btn" data-action="toggle-profile-editor" data-target="avatar-editor">Edit picture</button>
                <div id="avatar-editor" class="profile-editor-popover profile-editor-avatar d-none" aria-label="Avatar editor">
                    <div class="profile-editor-head">
                        <strong>Picture mode</strong>
                        <button type="button" class="profile-editor-close" data-action="toggle-profile-editor" data-target="avatar-editor">&times;</button>
                    </div>
                    <label class="profile-editor-label">Fit
                        <select class="form-select form-select-sm" name="avatar_fit" form="profile-edit-form" data-preview-target="#profile-avatar-preview" data-preview-style="objectFit">
                            <?php foreach (['contain', 'stretch'] as $fitOption): ?>
                                <option value="<?php echo htmlspecialchars($fitOption); ?>" <?php echo $avatarFit === $fitOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($fitOption); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="profile-editor-label">Size
                        <input type="range" min="100" max="200" name="avatar_scale" form="profile-edit-form" value="<?php echo (int) $avatarScale; ?>" data-preview-target="#profile-avatar-preview" data-preview-style="scale">
                    </label>
                    <label class="profile-editor-label">Horizontal position
                        <input type="range" min="0" max="100" name="avatar_pos_x" form="profile-edit-form" value="<?php echo (int) $avatarPosX; ?>" data-preview-target="#profile-avatar-preview" data-preview-style="objectPositionX">
                    </label>
                    <label class="profile-editor-label">Vertical position
                        <input type="range" min="0" max="100" name="avatar_pos_y" form="profile-edit-form" value="<?php echo (int) $avatarPosY; ?>" data-preview-target="#profile-avatar-preview" data-preview-style="objectPositionY">
                    </label>
                    <label for="avatar_file" class="btn btn-sm btn-outline-light profile-editor-upload">Replace image</label>
                </div>
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

                    <form method="POST" action="Profile.php" class="profile-edit-form" enctype="multipart/form-data" id="profile-edit-form">
                        <label for="profile_description" class="form-label profile-label mb-0">Description</label>
                        <textarea id="profile_description" class="form-control" name="profile_description" rows="5" maxlength="2000" placeholder="Write your profile description..."><?php echo htmlspecialchars($profileDescription); ?></textarea>

                        <input id="avatar_file" class="visually-hidden profile-file-input" name="avatar_file" type="file" accept="image/jpeg, image/png, image/gif, image/webp" data-preview-target="#profile-avatar-preview">
                        <input id="background_file" class="visually-hidden profile-file-input" name="background_file" type="file" accept="image/jpeg, image/png, image/gif, image/webp" data-preview-target="#profile-background-preview">
                        <p class="profile-file-hint mb-0">Use the small edit buttons on the image itself to adjust display or replace the file. Save to apply.</p>

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
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>



