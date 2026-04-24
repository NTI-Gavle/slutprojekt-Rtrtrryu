<div id="mySidenav" class="sidenav">
  <a href="#" class="closebtn" onclick="closeNav()">&times;</a>
  <a href="index.php">Explore</a>
  <a href="https://www.youtube.com/watch?v=2ltPZ6pl6JI&list=RD2ltPZ6pl6JI&start_radio=1">For You</a>
  <a href="Profile.php">Profile</a>
  <a href="https://www.youtube.com/watch?v=U06jlgpMtQs&list=RDU06jlgpMtQs&start_radio=1">Settings</a>
</div>
<span style="font-size:30px;cursor:pointer" onclick="openNav()">&#9776; open</span>

<script>
function openNav() {
  document.getElementById("mySidenav").style.width = "250px";
  document.getElementById("header").classList.add("openNav")
}

function closeNav() {
  document.getElementById("mySidenav").style.width = "0";
  document.getElementById("header").classList.remove("openNav")
}
</script>

