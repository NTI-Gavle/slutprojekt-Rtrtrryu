<?php
session_start();

require_once __DIR__ . '/../../database/db.php';

if (!isset($_GET['post_id'])){
    die('Post Does not exist');
}

$stmt = $dbconn->prepare('SELECT * FROM likes WHERE user_id = ? AND post_id = ?');
$stmt->execute([$_SESSION['user_id'], $_GET['post_id']]);
$likes = $stmt->fetch(PDO::FETCH_ASSOC);

if ($likes == null)
{
    $stmt = $dbconn->prepare('INSERT INTO likes(post_id, user_id) VALUES (?,?)');
    $stmt ->execute([$_GET['post_id'], $_SESSION['user_id']]);
    echo('liked');
}
else
{
    $stmt = $dbconn->prepare('DELETE FROM likes WHERE user_id = ? AND post_id = ?');
    $stmt->execute([$_SESSION['user_id'], $_GET['post_id']]);
    echo('unliked');
}

