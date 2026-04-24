<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = "Register";

$errormsg = "";
if (isset($_SESSION["Registererror"])) {
    $errormsg = $_SESSION["Registererror"];
    unset($_SESSION["Registererror"]);
}

$extraStyles = ['css/auth/login.css'];
require_once __DIR__ . '/../includes/header.php';
?>
<body class="bg-dark">
    

<div class="Login auth-page text-light">
    <h1>Skapa ett konto</h1>
    <form action="register.php" method="post" class="submit">
        username:
        <input type="text" name="username" class="input <?php if ($errormsg != "") echo "Error"; ?>" id="name"><br>
        password:
        <input type="password" name="password" class="input <?php if ($errormsg != "") echo "Error"; ?>" id="password"><br>
        Age:
        <input type="number" name="age" min="1" step="1" class="input <?php if ($errormsg != "") echo "Error"; ?>" id="age"><br>
        <button type="submit">Register</button><br>

        har du redan ett konto?<br>
        <div class="link"><a href="login.php">Tryck här</a></div>
    </form>

    <?php
    if ($errormsg != "") {
        echo "<p class='Errormsg'>" . htmlspecialchars($errormsg) . "</p>";
    }
    ?>
</div>
</body>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>


