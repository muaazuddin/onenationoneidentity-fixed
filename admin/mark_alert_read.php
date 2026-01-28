<?php
session_start();
include_once('../dbconn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alert_id'])) {
    $alertId = $_POST['alert_id'];
    
    // Update database
    $stmt = $conn->prepare("UPDATE admin_alerts SET is_read = 1 WHERE alert_id = ?");
    $stmt->bind_param("s", $alertId);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>