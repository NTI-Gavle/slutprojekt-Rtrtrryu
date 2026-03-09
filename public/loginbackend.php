<?php
session_start();



if (!isset($_POST["namn"], $_POST["lösenord"])) {
    header("Location: login.php");
    exit;
}

require "../database/db.php";

$user = $_POST["namn"];
$pass = $_POST["lösenord"];

$sql = "SELECT * FROM användare WHERE namn = ?";
$stmt = $dbconn->prepare($sql);
$stmt->execute([$user]);

$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (password_verify($pass, $result["lösenord"])) {
    $_SESSION["user_id"] = $result["id"];
    $_SESSION["username"] = $result["namn"];
    header("location: index.php");
    die();
} else {
    $_SESSION["loginerror"] = "wrong username or password";
}


$_SESSION["hi"]="TEST";
header("Location: login.php");
exit;