<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$headerAvatarPath = null;
$headerAvatarStyle = '';

/* kollar om user är inloggad med att kolla om attiva användaren har ett id igång*/
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../database/db.php';
    require_once __DIR__ . '/../database/user_queries.php';

    $headerProfile = getUserProfileData($dbconn, (int) $_SESSION['user_id']);
    if ($headerProfile !== null && !empty($headerProfile['avatar_path'])) {
        $headerAvatarPath = (string) $headerProfile['avatar_path'];
        $_SESSION['avatar_path'] = $headerAvatarPath;
        $headerAvatarStyle = buildAvatarDisplayStyle(
            $headerProfile['avatar_fit'] ?? 'contain',
            $headerProfile['avatar_pos_x'] ?? 50,
            $headerProfile['avatar_pos_y'] ?? 50,
            $headerProfile['avatar_scale'] ?? 100
        );
    } elseif (isset($_SESSION['avatar_path']) && trim((string) $_SESSION['avatar_path']) !== '') {
        $headerAvatarPath = (string) $_SESSION['avatar_path'];
    }
}

$assetBasePath = '';
if (isset($assetBasePath) && is_string($assetBasePath) && trim($assetBasePath) !== '') {
    $assetBasePath = rtrim($assetBasePath, '/') . '/';
} else {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (str_ends_with($scriptDir, '/frontend') || str_ends_with($scriptDir, '/backend')) {
        $assetBasePath = '../';
    }
}

if (!function_exists('site_asset_url')) {
    function site_asset_url(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $path) || str_starts_with($path, 'data:')) {
            return $path;
        }

        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $basePath = (str_ends_with($scriptDir, '/frontend') || str_ends_with($scriptDir, '/backend')) ? '../' : '';

        return $basePath . ltrim($path, '/');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'My Home Project' ?></title>

    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBasePath . 'css/base/style.css'); ?>">
    <?php if (isset($extraStyles) && is_array($extraStyles)): ?>
        <?php foreach ($extraStyles as $extraStyle): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBasePath . ltrim((string) $extraStyle, '/')); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <!-- JS -->
    <script src="<?php echo htmlspecialchars($assetBasePath . 'js/app.js'); ?>" defer></script>
</head>

<body<?php echo isset($bodyClass) ? ' class="' . htmlspecialchars((string) $bodyClass) . '"' : ''; ?><?php echo isset($bodyStyle) ? ' style="' . htmlspecialchars((string) $bodyStyle) . '"' : ''; ?>>


<header id="header" class="site-header">
    <div class="header-container">
        <div class="header-user">
            <?php if (isset($_SESSION["user_id"])): ?>
                <a href="Profile.php" class="header-user-link text-decoration-none text-white">
                    <?php if (!empty($headerAvatarPath)): ?>
                        <span class="profilepic profilepic-frame">
                            <img src="<?php echo htmlspecialchars(site_asset_url($headerAvatarPath)); ?>" alt="Profile picture" class="profilepic-image" style="<?php echo htmlspecialchars($headerAvatarStyle); ?>">
                        </span>
                    <?php else: ?>
                        <div class="profilepic rounded-circle text-white">Pfp</div>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars((string) $_SESSION["username"]); ?></span>
                </a>
            <?php endif; ?>
        </div>

        <h1 class="site-title">
            <a href="index.php">Rule 89</a>
        </h1>

        <?php require __DIR__ . '/nav.php'; ?>
    </div>
</header>
