<?php
session_start();

if (!isset($_POST["username"], $_POST["password"])) {
    header("Location: ../frontend/login.php");
    exit;
}

require __DIR__ . "/../../database/db.php";
require_once __DIR__ . "/../../database/user_queries.php";

$user = (string) $_POST["username"];
$pass = (string) $_POST["password"];

$userMeta = getUserTableMeta($dbconn);
if ($userMeta === null) {
    $_SESSION["loginerror"] = "Could not find the user table.";
    header("Location: ../frontend/login.php");
    exit;
}

$columns = getTableColumns($dbconn, $userMeta['table']);
$passwordColumn = findColumn($columns, ['lösenord', 'password', 'pass']);
if ($passwordColumn === null) {
    $_SESSION["loginerror"] = "User table is missing the password column.";
    header("Location: ../frontend/login.php");
    exit;
}

$sql = "SELECT * FROM `{$userMeta['table']}` WHERE `{$userMeta['name_column']}` = ? LIMIT 1";
$stmt = $dbconn->prepare($sql);
$stmt->execute([$user]);

$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    $_SESSION["loginerror"] = "wrong username or password";
    header("Location: ../frontend/login.php");
    exit;
}

$storedPassword = (string) ($result[$passwordColumn] ?? '');

if (password_verify($pass, $storedPassword)) {
    $idColumn = $userMeta['id_column'];
    $_SESSION["user_id"] = $result[$idColumn];
    $_SESSION["username"] = $result[$userMeta['name_column']];
    header("Location: ../frontend/index.php");
    exit;
}

$_SESSION["loginerror"] = "wrong username or password";
header("Location: ../frontend/login.php");
exit;

