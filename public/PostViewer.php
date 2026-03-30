<?php
require_once __DIR__ . '/../includes/header.php';
include('../database/db.php');

// Check adult status
$userIsAdult = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $dbconn->prepare("SELECT ålder FROM användare WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $userIsAdult = (int)$user['ålder'] >= 18;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/style.css">
    <script src="js/app.js" defer></script>

    <title>Document</title>
</head>
<body onload="RefreshLikes(<?php echo($_GET['post_id']); ?>)">
<div class="likes">
    <button class="btn w-100 h-100" id="like" onclick="Like(<?php echo($_GET['post_id']); ?>)"></button>
</div>
</body>
</html>