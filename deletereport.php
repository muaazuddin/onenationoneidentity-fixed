<?php 
    include ('dbconn.php');
    $serial=$_GET['serial'];
    $precription_id = $_GET['id'];
    mysqli_query($conn,"delete from testreport where serial ='$serial'");
    header("location:hosprescriptionview.php?id=".$precription_id);

?>
<?php include('lib_db.php'); ?>