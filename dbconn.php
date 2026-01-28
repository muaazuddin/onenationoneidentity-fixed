<?php 
$conn = mysqli_connect("localhost", "root", "", "onenationoneidentity_cs");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
<?php include('lib_db.php'); ?>