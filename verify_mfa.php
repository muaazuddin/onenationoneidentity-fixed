<?php
session_start();
require_once 'lib_db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['mfa_user_type']) || !isset($_SESSION['mfa_user_nid'])) {
        die("Session expired. Please login again.");
    }

    $otp_input = trim($_POST['otp']);
    $user_type = $_SESSION['mfa_user_type'];
    $user_nid  = $_SESSION['mfa_user_nid'];
    $role      = $_SESSION['mfa_role'];

    $hash = hash('sha256', $otp_input . 'SOME_STATIC_SALT');

    $rows = db_query("SELECT * FROM mfa_tokens 
                      WHERE user_type=? AND user_nid=? AND otp_hash=? 
                      ORDER BY id DESC LIMIT 1",
                      [$user_type, $user_nid, $hash]);

    if ($rows) {
        $row = $rows[0];
        if ($row['used']) die("OTP already used");
        if (strtotime($row['expires_at']) < time()) die("OTP expired");

        // mark as used
        db_query("UPDATE mfa_tokens SET used=1 WHERE id=?", [$row['id']]);

        // Now set full login
        $_SESSION['id']    = $user_nid;
        $_SESSION['radio'] = $role;

        if ($role == 'Patient') {
            header("Location: patienthome.php");
        } else {
            header("Location: doctorhome.php");
        }
        exit;
    } else {
        die("❌ Invalid OTP");
    }
} else {
    header("Location: personlogin.php");
    exit;
}
