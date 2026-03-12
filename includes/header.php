<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'My Home Project' ?></title>

    <!-- CSS -->
    <link rel="stylesheet" href="css/styles.css">

    <!-- JS -->
    <script src="js/app.js" defer></script>
</head>
<body>
<header id="header" class="site-header">
    <div class="header-container">
        <h1 class="site-title">
            <a href="index.php">Rule 89</a>
        </h1>

        <?php require __DIR__ . '/nav.php'; ?>
    </div>
</header>

