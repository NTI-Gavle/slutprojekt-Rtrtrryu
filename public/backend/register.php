<?php
session_start();

if (!isset($_POST["username"], $_POST["password"], $_POST["age"])) {
    header("Location: ../frontend/registerpage.php");
    exit;
}

require __DIR__ . "/../../database/db.php";
require_once __DIR__ . "/../../database/user_queries.php";

$user = (string) $_POST["username"];
$pass = (string) $_POST["password"];
$age = (int) $_POST["age"];

if (mb_strlen($user) > 25) {
    $_SESSION["Registererror"] = "Username must be 25 characters or fewer";
    header("Location: ../frontend/registerpage.php");
    exit;
}

if ($age < 1) {
    $_SESSION["Registererror"] = "Age must be a positive number";
    header("Location: ../frontend/registerpage.php");
    exit;
}

$userTable = resolveUserTable($dbconn);
$userMeta = $userTable !== null ? getUserTableMeta($dbconn) : null;

if ($userMeta === null) {
    $_SESSION["Registererror"] = "Could not find the user table.";
    header("Location: ../frontend/registerpage.php");
    exit;
}

$nameColumn = $userMeta['name_column'];
$passwordColumn = findColumn(getTableColumns($dbconn, $userMeta['table']), ['lösenord', 'lsenord', 'password', 'pass']);
$ageColumn = findColumn(getTableColumns($dbconn, $userMeta['table']), ['alder', 'ålder', 'Ã¥lder', 'lder', 'age']);

if ($passwordColumn === null || $ageColumn === null) {
    $_SESSION["Registererror"] = "User table is missing required columns.";
    header("Location: ../frontend/registerpage.php");
    exit;
}

$sql = "SELECT 1 FROM `{$userMeta['table']}` WHERE `{$nameColumn}` = ? LIMIT 1";
$stmt = $dbconn->prepare($sql);
$stmt->execute([$user]);

$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!empty($result)) {
    $_SESSION["Registererror"] = "User already exists";
    header("Location: ../frontend/registerpage.php");
    exit;
}

$sql = "INSERT INTO `{$userMeta['table']}` (`{$nameColumn}`, `{$passwordColumn}`, `{$ageColumn}`) VALUES (?, ?, ?)";
$stmt = $dbconn->prepare($sql);
$stmt->execute([$user, password_hash($pass, PASSWORD_DEFAULT), $age]);

$_SESSION["RegisterSuccess"] = "Registration succsess";
header("Location: ../frontend/login.php");
exit;


