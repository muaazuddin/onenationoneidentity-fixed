<?php
include_once('../dbconn.php'); 

if (isset($_GET['hospital_id'])) {
    $hospital_id = $_GET['hospital_id'];
    $conn->query("DELETE FROM hospital WHERE hospital_id = '$hospital_id'");
}

header("Location: manage_hospitals.php");
exit();
?>
