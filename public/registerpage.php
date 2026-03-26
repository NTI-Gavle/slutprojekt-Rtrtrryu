<?php

use Dom\Document;
    require_once __DIR__ . '/../includes/header.php';
    
    $errormsg="";
    if(isset($_SESSION["Registererror"])){
        $errormsg=$_SESSION["Registererror"];
        unset($_SESSION["Registererror"]);
    }
    
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    
    
<div class="Login">
    <h1>Skapa ett konto</h1>
    <form action="register.php" method="post" class="submit">
        username:
        <input type="text" name="namn" class="input <?php if ($errormsg!="") echo "Error"; ?>" id="name"><br>
        password:
        <input type="password" name="lösenord" class="input <?php if ($errormsg!="") echo "Error"; ?>" id="password"><br>
        <button type="submit">Login</button><br>
        
        har du redan ett konto?<br>
        <div class="link"><a>Tryck</a> <a href="login.php">här</a></div>
    </form>

<?php
    if($errormsg!=""){
        echo "<p class='Errormsg'>". $errormsg . "</p>";
        
    }
?>

</body>
</html>