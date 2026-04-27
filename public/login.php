<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Login';
$bodyClass = 'bg-dark auth-fixed-header';
$extraStyles = ['css/auth/login.css'];

$errormsg = '';
if (isset($_SESSION['loginerror'])) {
    $errormsg = $_SESSION['loginerror'];
    unset($_SESSION['loginerror']);
}

$succsesmsg = '';
if (isset($_SESSION['RegisterSuccess'])) {
    $succsesmsg = $_SESSION['RegisterSuccess'];
    unset($_SESSION['RegisterSuccess']);
}

?>

<?php
require_once __DIR__ . '/../includes/header.php';
?>

<div class="Login auth-page text-light">
    <form action="loginbackend.php" method="post" class="submit">
        <h1>Logga in</h1>
        username:
        <input type="text" name="username" maxlength="25" class="input <?php if ($errormsg != '') echo 'Error'; ?> <?php if ($succsesmsg != '') echo 'Succses'; ?>" id="name"><br>
        password:
        <input type="password" name="password" class="input <?php if ($errormsg != '') echo 'Error'; ?> <?php if ($succsesmsg != '') echo 'Succses'; ?>" id="password"><br>
        <button type="submit">Login</button>
        <br>

        har du inget konto?<br>
        <div class="link"><a href="registerpage.php">Tryck här</a></div>
    </form>

    <?php
    if ($errormsg != '') {
        echo "<p class='Errormsg'>" . htmlspecialchars($errormsg) . "</p>";
    }
    if ($succsesmsg != '') {
        echo "<p class='succsesmsg'>" . htmlspecialchars($succsesmsg) . "</p>";
    }
    ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
