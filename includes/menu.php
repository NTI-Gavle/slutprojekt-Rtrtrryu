<div id="mySidenav" class="sidenav">
  <a href="#" class="closebtn" onclick="closeNav()">&times;</a>
  <a href="#">Explore</a>
  <a href="#">For You</a>
  <a href="#">Profile</a>
  <a href="#">Settings</a>
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

