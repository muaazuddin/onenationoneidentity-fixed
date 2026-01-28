<?php
session_start();
include_once('../dbconn.php');

// ------------------ GET REAL IP ------------------ //
function getRealIP() {
    $ip = '';

    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ipList[0]);
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }

    if ($ip === '::1') {
        $ip = '127.0.0.1';
    }

    return $ip;
}

// ------------------ EMAIL FUNCTION ------------------ //
function sendVerificationEmail($user_email, $verification_code, $username) {
    // Admin email (sender)
    $admin_email = "shossain221492@bscse.uiu.ac.bd";
    
    $to = $user_email;
    $subject = "Admin Login Verification Code";
    
    $message = "
    <html>
    <head>
    <title>Admin Login Verification</title>
    </head>
    <body>
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #4361ee;'>Admin Portal Verification</h2>
        <p>Hello $username,</p>
        <p>Your verification code for admin login is:</p>
        <div style='background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px; color: #4361ee; margin: 20px 0;'>
            $verification_code
        </div>
        <p>This code will expire in 10 minutes. If you didn't request this code, please ignore this email.</p>
        <hr>
        <p style='font-size: 12px; color: #6c757d;'>This is an automated message from Admin Portal, please do not reply to this email.</p>
    </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Admin Portal <$admin_email>" . "\r\n";
    $headers .= "Reply-To: $admin_email" . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// ------------------ ALERT SYSTEM ------------------ //
function addAdminAlert($type, $message, $admin_id = null) {
    global $conn;

    $alert_id = uniqid('alert_', true);
    $timestamp = time();
    $ip_address = getRealIP();

    $stmt = $conn->prepare("INSERT INTO admin_alerts 
        (alert_id, type, message, timestamp, ip_address, admin_id) 
        VALUES (?, ?, ?, ?, ?, ?)");

    if ($stmt === false) {
        error_log("SQL Prepare Error: " . $conn->error);
        return false;
    }

    $stmt->bind_param("sssisi", $alert_id, $type, $message, $timestamp, $ip_address, $admin_id);

    if (!$stmt->execute()) {
        error_log("SQL Execute Error: " . $stmt->error);
    }
    $stmt->close();

    return [
        'id' => $alert_id,
        'type' => $type,
        'message' => $message,
        'timestamp' => $timestamp,
        'ip_address' => $ip_address,
        'read' => false
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_SESSION['temp_admin_id'], $_SESSION['temp_username'], $_SESSION['temp_email'])) {
        // Generate new verification code
        $new_verification_code = rand(100000, 999999);
        
        // Update session with new code and expiration
        $_SESSION['verification_code'] = $new_verification_code;
        $_SESSION['code_expiration'] = time() + 600; // 10 minutes
        
        // Send email from admin email to user email
        $email_sent = sendVerificationEmail($_SESSION['temp_email'], $new_verification_code, $_SESSION['temp_username']);
        
        if ($email_sent) {
            // Log the resend action
            addAdminAlert('security', "2FA code resent via email", $_SESSION['temp_admin_id']);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Email sending failed']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Session expired']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>