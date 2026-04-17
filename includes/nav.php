<nav class="site-nav">
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="contact.php">Contact</a></li>
        <li><?php if(isset($_SESSION["user_id"])){
           echo "<a href='logout.php'>Logout</a>";
        }
        else {
            echo "<a href='login.php'>Login</a>";
        }

        ?>
        </li>
    </ul>
</nav>