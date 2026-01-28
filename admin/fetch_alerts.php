<?php
include_once('../dbconn.php');
header('Content-Type: application/json');

// Get latest 5 unread alerts
$sql = "SELECT id, message, type, created_at FROM admin_alerts 
        WHERE is_read = 0 
        ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($sql);

$alerts = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $alerts[] = $row;
    }
}

echo json_encode($alerts);
