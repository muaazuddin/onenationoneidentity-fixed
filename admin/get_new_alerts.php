<?php
session_start();
include_once('../dbconn.php');

header('Content-Type: application/json');

$response = [];

// Get the latest alerts (in a real implementation, you would filter by timestamp)
$stmt = $conn->prepare("SELECT alert_id as id, type, message, timestamp, is_read as read FROM admin_alerts ORDER BY timestamp DESC LIMIT 5");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $response[] = $row;
}

echo json_encode($response);
?>