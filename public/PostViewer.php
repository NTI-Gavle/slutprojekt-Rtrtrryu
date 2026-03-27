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