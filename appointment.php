<?php          
    include("dbconn.php");
    include("session.php");
    $did = $_GET['dnid'];
    $pid = $_GET['pnid'];
    mysqli_query($conn,"insert into appointment (p_nid ,d_nid,appointment,date) values ('$pid','$did','yes',NOW())");
    header("location:doctorprofile.php?id=".$did);
?>
<?php include('lib_db.php'); ?>