<?php
session_start();

$session_timeout = 300;
$response = ['active' => true];

if (isset($_SESSION['admin_username'])) {
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $session_timeout)) {
        $response['active'] = false;
        $response['reason'] = 'Session expired';
    } else {
        $_SESSION['LAST_ACTIVITY'] = time();
        $response['last_activity'] = $_SESSION['LAST_ACTIVITY'];
        $response['time_remaining'] = $session_timeout - (time() - $_SESSION['LAST_ACTIVITY']);
    }
} else {
    $response['active'] = false;
    $response['reason'] = 'Not logged in';
}

header('Content-Type: application/json');
echo json_encode($response);
?>