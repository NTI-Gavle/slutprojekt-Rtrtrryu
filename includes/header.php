<?php
session_start();
$headerAvatarPath = null;
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../database/db.php';
    require_once __DIR__ . '/../database/user_queries.php';

    $headerProfile = getUserProfileData($dbconn, (int) $_SESSION['user_id']);
    if ($headerProfile !== null && !empty($headerProfile['avatar_path'])) {
        $headerAvatarPath = (string) $headerProfile['avatar_path'];
        $_SESSION['avatar_path'] = $headerAvatarPath;
    } elseif (isset($_SESSION['avatar_path']) && trim((string) $_SESSION['avatar_path']) !== '') {
        $headerAvatarPath = (string) $_SESSION['avatar_path'];
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
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <!-- JS -->
    <script src="js/app.js" defer></script>
</head>
<body>
<header id="header" class="site-header">
<?php
if (isset($_SESSION["user_id"])) {
    if (!empty($headerAvatarPath)) {
        echo '<img src="' . htmlspecialchars($headerAvatarPath) . '" alt="Profile picture" class="profilepic rounded-circle">';
    } else {
        echo '<div class="profilepic rounded-circle text-white">Pfp</div>';
    }
    echo htmlspecialchars((string) $_SESSION["username"]);
}
?>
    <div class="header-container">
        <h1 class="site-title">
            <a href="index.php">Rule 89</a>
        </h1>

        <?php require __DIR__ . '/nav.php'; ?>
    </div>
</header>

