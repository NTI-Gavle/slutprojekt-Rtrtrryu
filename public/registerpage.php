<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Register';
$bodyClass = 'bg-dark auth-fixed-header';
$extraStyles = ['css/auth/login.css'];

$errormsg = '';
if (isset($_SESSION['Registererror'])) {
    $errormsg = $_SESSION['Registererror'];
    unset($_SESSION['Registererror']);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="Login auth-page text-light">
    <h1>Skapa ett konto</h1>
    <form action="register.php" method="post" class="submit">
        username:
        <input type="text" name="username" maxlength="25" class="input <?php if ($errormsg != '') echo 'Error'; ?>" id="name"><br>
        password:
        <input type="password" name="password" class="input <?php if ($errormsg != '') echo 'Error'; ?>" id="password"><br>
        Age:
        <input type="number" name="age" min="1" step="1" class="input <?php if ($errormsg != '') echo 'Error'; ?>" id="age"><br>
        <button type="submit">Register</button><br>

        har du redan ett konto?<br>
        <div class="link"><a href="login.php">Tryck här</a></div>
    </form>

    <?php
    if ($errormsg != '') {
        echo "<p class='Errormsg'>" . htmlspecialchars($errormsg) . "</p>";
    }
    ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
