<nav class="site-nav">
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="https://www.youtube.com/watch?v=Aqi86gt7YB4">About</a></li>
        <li><a href="https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=RDdQw4w9WgXcQ&start_radio=1">Contact</a></li>
        <li><?php if(isset($_SESSION["user_id"])){
           echo "<a href='../backend/logout.php'>Logout</a>";
        }
        else {
           echo "<a href='login.php'>Login</a>";
        }

        ?>
        </li>
    </ul>
</nav>
