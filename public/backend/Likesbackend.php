<?php
session_start();

require_once __DIR__ . '/../../database/db.php';


if (!isset($_GET['post_id'])){
    die('Post Does not exist');
}


$stmt = $dbconn->prepare('SELECT * FROM likes WHERE post_id = ?');
$stmt->execute([$_GET['post_id']]);
$likes = $stmt->fetchAll(PDO::FETCH_ASSOC);


$user_ids = array_column($likes, 'user_id');

echo(json_encode([
    "status" => (bool)in_array($_SESSION['user_id'], $user_ids),
    "likes" => count($likes)
]));
