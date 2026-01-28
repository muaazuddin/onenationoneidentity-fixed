<?php
session_start();
require_once 'lib_db.php';

// ---------------------
// Helper functions
// ---------------------
function generate_otp($length = 6) {
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= rand(0, 9);
    }
    return $otp;
}

function create_mfa_token($user_type, $user_nid, $otp) {
    $hash = hash('sha256', $otp . 'SOME_STATIC_SALT'); // static salt for demo
    $expires = date('Y-m-d H:i:s', time() + 600); // 10 minutes expiry

    db_query(
        "INSERT INTO mfa_tokens (user_type,user_nid,otp_hash,expires_at) VALUES (?,?,?,?)",
        [$user_type, $user_nid, $hash, $expires]
    );
}

// ---------------------
// Login process
// ---------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nid = trim($_POST['nid']);
    $password = trim($_POST['password']);

    // Check credentials (example: patient table, adjust if needed)
    $rows = db_query("SELECT * FROM patient WHERE nid=? AND password=?", [$nid, $password]);

    if (!$rows) {
        die("❌ Invalid NID or Password");
    }

    $user = $rows[0];
    $email = $user['email'];  // must exist in your DB

    if (empty($email)) {
        die("❌ No email registered for this user. Cannot send OTP.");
    }

    // ✅ Password correct → now generate OTP
    $otp = generate_otp();
    create_mfa_token('patient', $nid, $otp);

    // Send OTP via email
    $subject = "Your Login OTP Code";
    $message = "Hello,\n\nYour OTP code is: $otp\nThis code will expire in 10 minutes.\n\nDo not share it with anyone.";
    $headers = "From: noreply@yourdomain.com";

    if (mail($email, $subject, $message, $headers)) {
        echo "<p>✅ An OTP has been sent to your email address ($email)</p>";
    } else {
        echo "<p>⚠ Could not send OTP email. (For testing, OTP is: <b>$otp</b>)</p>";
    }

    // Save user info temporarily in session (for MFA step)
    $_SESSION['mfa_user_type'] = 'patient';
    $_SESSION['mfa_user_nid']  = $nid;

    // Show OTP form
    echo "<form method='post' action='verify_mfa.php'>
            Enter OTP: <input type='text' name='otp' required>
            <button type='submit'>Verify</button>
          </form>";
} else {
    header("Location: login.php");
    exit;
}
