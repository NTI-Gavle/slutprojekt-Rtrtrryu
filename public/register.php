<?php
session_start();

if (!isset($_POST["username"], $_POST["password"], $_POST["age"])) {
    header("Location: registerpage.php");
    exit;
}

require __DIR__ . "/../database/db.php";

$user = (string) $_POST["username"];
$pass = (string) $_POST["password"];
$age = (int) $_POST["age"];

if (mb_strlen($user) > 25) {
    $_SESSION["Registererror"] = "Username must be 25 characters or fewer";
    header("Location: registerpage.php");
    exit;
}

if ($age < 1) {
    $_SESSION["Registererror"] = "Age must be a positive number";
    header("Location: registerpage.php");
    exit;
}

$sql = "SELECT * FROM användare WHERE namn = ?";
$stmt = $dbconn->prepare($sql);
$stmt->execute([$user]);

$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!empty($result)) {
    $_SESSION["Registererror"] = "User already exists";
    header("Location: registerpage.php");
    exit;
}

$sql = "INSERT INTO användare (namn, lösenord, ålder) VALUES (?, ?, ?)";
$stmt = $dbconn->prepare($sql);
$stmt->execute([$user, password_hash($pass, PASSWORD_DEFAULT), $age]);

$_SESSION["RegisterSuccess"] = "Registration succsess";
header("Location: login.php");
exit;
