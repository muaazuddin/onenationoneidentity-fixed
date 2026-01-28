<?php
session_start();

if (isset($_POST['update_activity']) && isset($_SESSION['admin_username'])) {
    $_SESSION['LAST_ACTIVITY'] = time();
    echo json_encode(['success' => true, 'timestamp' => $_SESSION['LAST_ACTIVITY']]);
} else {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
}
?>