<?php

session_start();

if(!(isset($_POST["namn"]) && isset($_POST["lösenord"]) && isset($_POST["ålder"])))
{
    header("Location: registerpage.php");
}

include("../database/db.php");


$user = $_POST["namn"];
$pass = $_POST["lösenord"];
$age = $_POST["ålder"];

$sql = "SELECT * FROM användare where namn=?";
$stmt = $dbconn->prepare($sql);

// parameters in array, if empty we could skip the $data-variable
$data = array($user);
$stmt->execute($data);

$result = $stmt->fetch(PDO::FETCH_ASSOC);

if(!empty($result))
{
    $_SESSION["Registererror"] =  "User already exists";
    header("Location: register.php");
    die();
}

$user = $_POST["namn"];
$pass = $_POST["lösenord"];
$age = $_POST["ålder"];

$sql = "INSERT INTO användare (namn,lösenord,ålder) VALUES (?,?,?)";
$stmt = $dbconn->prepare($sql);

// parameters in array, if empty we could skip the $data-variable
$data = array($user, password_hash($pass,PASSWORD_DEFAULT),$age);

$stmt->execute(params: $data);

$_SESSION["RegisterSuccess"] = "Registration succsess";

header("Location: login.php");


