<?php
$pageTitle = "Home"; // <-- set dynamic page title
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="css/style.css">
<div style="width:100%; display: flex; flex-direction:column;">
    <div width="5%">
    <?php
    include('../includes/menu.php');
    ?>
    </div>
    <div class="width:95%;">

    </div>
</div>
</div>
<button href="Post.php" defer>New Post</button>

<div class="post-window" id="post-container">
  <!-- posts go here dynamically -->
</div>

<!--<main class="main-content">

<h2>Welcome to My Home Project</h2>
<p>This is the homepage.</p>

</main>
<?php
require_once __DIR__ . '/../includes/footer.php';