<?php
include_once('../dbconn.php');


if (isset($_GET['nid'])) {
    $nid = $_GET['nid'];
    
    $stmt = $conn->prepare("DELETE FROM patient WHERE p_nid = ?");
    $stmt->bind_param("s", $nid);
    $stmt->execute();

    header("Location: manage_patients.php");
    exit();
} else {
    echo "No NID provided for deletion.";
}
?>
