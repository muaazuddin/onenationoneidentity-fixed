<?php
// user_management.php
require_once 'rbac.php';
session_start();
include_once('../dbconn.php');

$rbac = CompleteRBAC::getInstance($conn);
if (!$rbac) {
    header("Location: index.php");
    exit();
}

// This will auto-redirect odd ID admins to dashboard
$rbac->requirePageAccess('user_management.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Management</title>
</head>
<body>
    <?php echo $rbac->generateNavigationMenu(); ?>
    <div class="main-content" style="margin-left: 250px; padding: 20px;">
        <h1>User Management</h1>
        <p>This page is only accessible to Even ID Admins</p>
    </div>
</body>
</html>