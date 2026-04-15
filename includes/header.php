<?php
session_start();
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
    echo '<div class="profilepic rounded-circle"></div>' . $_SESSION["username"];
}
?>
    <div class="header-container">
        <h1 class="site-title">
            <a href="index.php">Rule 89</a>
        </h1>

        <?php require __DIR__ . '/nav.php'; ?>
    </div>
</header>

