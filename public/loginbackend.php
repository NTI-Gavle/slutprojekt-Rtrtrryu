<?php
session_start();

if (!isset($_POST["username"], $_POST["password"])) {
    header("Location: login.php");
    exit;
}

require __DIR__ . "/../database/db.php";

$user = (string) $_POST["username"];
$pass = (string) $_POST["password"];

$sql = "SELECT * FROM användare WHERE namn = ?";
$stmt = $dbconn->prepare($sql);
$stmt->execute([$user]);

$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    $_SESSION["loginerror"] = "wrong username or password";
    header("Location: login.php");
    exit;
}

if (password_verify($pass, (string) $result["lösenord"])) {
    $_SESSION["user_id"] = $result["id"];
    $_SESSION["username"] = $result["namn"];
    header("Location: index.php");
    exit;
}

$_SESSION["loginerror"] = "wrong username or password";
header("Location: login.php");
exit;
