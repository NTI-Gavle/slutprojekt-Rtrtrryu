<?php
  session_start();
  
    
    $errormsg="";
    if(isset($_SESSION["loginerror"])){
        $errormsg=$_SESSION["loginerror"];
        unset($_SESSION["loginerror"]);
    }
    $succsesmsg="";
    if(isset($_SESSION["RegisterSuccess"])){
        $succsesmsg=$_SESSION["RegisterSuccess"];
        unset($_SESSION["RegisterSuccess"]);
    }
    require_once __DIR__ . '/../includes/header.php';
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
    <form action="loginbackend.php" method="post" class="submit">
        <h1>Logga in</h1>
        username:
        <input type="text" name="namn" class="input <?php if ($errormsg!="") echo "Error"; ?> <?php if ($succsesmsg!="") echo "Succses"; ?>" id="name"><br>
        password:
        <input type="password" name="lösenord" class="input <?php if ($errormsg!="") echo "Error"; ?> <?php if ($succsesmsg!="") echo "Succses"; ?>" id="password"><br>
        <button type="submit">Login</button>
        <br>

        har du inget konto?<br>
        <div class="link"><a>Tryck</a> <a href="registerpage.php">här</a></div>
    </form>

<?php
    if($errormsg!=""){
        echo "<p class='Errormsg'>". $errormsg . "</p>";
        
    }
    if($succsesmsg!=""){
        echo "<p class='succsesmsg'>". $succsesmsg. "</p>";
    }
?>

</div>
</body>
</html>