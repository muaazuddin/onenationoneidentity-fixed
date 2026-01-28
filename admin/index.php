<?php
session_start();
include_once('../dbconn.php');

// ------------------ SECURITY CONFIGURATION ------------------ //
$session_timeout = 300;
$warning_timeout = 30;  
$max_login_attempts = 5;
$lockout_duration = 120; 
$captcha_after_attempts = 2;

// ------------------ RBAC SYSTEM ------------------ //
require_once 'rbac.php';

// ------------------ ACCOUNT LOCKOUT FUNCTIONS ------------------ //
function trackLoginAttempt($username, $ip_address, $success) {
    global $conn, $max_login_attempts, $lockout_duration;
    
    $timestamp = time();
    $current_time = date('Y-m-d H:i:s', $timestamp);
    
    if ($success) {
        // Successful login - reset attempts for this username
        $stmt = $conn->prepare("DELETE FROM login_attempts WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->close();
        return ['success' => true];
    }
    
    // Check existing attempts for this username
    $stmt = $conn->prepare("SELECT id, attempts, first_attempt FROM login_attempts WHERE username = ? ORDER BY first_attempt DESC LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $attempts = $row['attempts'] + 1;
        $first_attempt = $row['first_attempt'];
        $record_id = $row['id'];
        
        // Check if should be locked
        if ($attempts >= $max_login_attempts) {
            $lockout_until = strtotime($first_attempt) + $lockout_duration;
            if ($timestamp < $lockout_until) {
                $remaining_time = $lockout_until - $timestamp;
                return [
                    'locked' => true,
                    'remaining_time' => $remaining_time,
                    'attempts' => $attempts,
                    'message' => "Account locked. Try again in " . ceil($remaining_time / 60) . " minutes."
                ];
            } else {
                // Lockout period expired, reset to 1 attempt
                $attempts = 1;
                $first_attempt = $current_time;
                
                $stmt = $conn->prepare("UPDATE login_attempts SET attempts = ?, first_attempt = ?, last_attempt = ?, ip_address = ? WHERE id = ?");
                $stmt->bind_param("isssi", $attempts, $first_attempt, $current_time, $ip_address, $record_id);
            }
        } else {
            // Increment attempts
            $stmt = $conn->prepare("UPDATE login_attempts SET attempts = ?, last_attempt = ?, ip_address = ? WHERE id = ?");
            $stmt->bind_param("issi", $attempts, $current_time, $ip_address, $record_id);
        }
    } else {
        // First failed attempt for this username
        $attempts = 1;
        $stmt = $conn->prepare("INSERT INTO login_attempts (username, ip_address, attempts, first_attempt, last_attempt) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $username, $ip_address, $attempts, $current_time, $current_time);
    }
    
    $stmt->execute();
    $stmt->close();
    
    return [
        'locked' => false,
        'attempts' => $attempts,
        'max_attempts' => $max_login_attempts,
        'remaining_attempts' => $max_login_attempts - $attempts
    ];
}

function checkAccountLockout($username, $ip_address) {
    global $conn, $max_login_attempts, $lockout_duration;
    
    $timestamp = time();
    
    // Clean up expired lockouts
    $cleanup_time = date('Y-m-d H:i:s', $timestamp - $lockout_duration);
    $cleanup_stmt = $conn->prepare("DELETE FROM login_attempts WHERE first_attempt < ?");
    $cleanup_stmt->bind_param("s", $cleanup_time);
    $cleanup_stmt->execute();
    $cleanup_stmt->close();
    
    // Check only for this username
    $stmt = $conn->prepare("SELECT attempts, first_attempt FROM login_attempts WHERE username = ? ORDER BY first_attempt DESC LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $attempts = $row['attempts'];
        $first_attempt = strtotime($row['first_attempt']);
        
        if ($attempts >= $max_login_attempts) {
            $lockout_until = $first_attempt + $lockout_duration;
            if ($timestamp < $lockout_until) {
                $remaining_time = $lockout_until - $timestamp;
                return [
                    'locked' => true,
                    'remaining_time' => $remaining_time,
                    'attempts' => $attempts,
                    'lockout_until' => date('Y-m-d H:i:s', $lockout_until),
                    'message' => "Account temporarily locked. Please try again in " . ceil($remaining_time / 60) . " minutes."
                ];
            } else {
                // Lockout expired - delete record
                $delete_stmt = $conn->prepare("DELETE FROM login_attempts WHERE username = ?");
                $delete_stmt->bind_param("s", $username);
                $delete_stmt->execute();
                $delete_stmt->close();
                
                return [
                    'locked' => false,
                    'attempts' => 0,
                    'max_attempts' => $max_login_attempts,
                    'remaining_attempts' => $max_login_attempts
                ];
            }
        }
        
        return [
            'locked' => false,
            'attempts' => $attempts,
            'max_attempts' => $max_login_attempts,
            'remaining_attempts' => $max_login_attempts - $attempts
        ];
    }
    
    return [
        'locked' => false,
        'attempts' => 0,
        'max_attempts' => $max_login_attempts,
        'remaining_attempts' => $max_login_attempts
    ];
}

function getRemainingAttempts($username, $ip_address) {
    $lockout_status = checkAccountLockout($username, $ip_address);
    return $lockout_status['remaining_attempts'];
}

// ------------------ SESSION TIMEOUT ------------------ //
if (isset($_SESSION['admin_username']) && isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $session_timeout)) {
    if (isset($_SESSION['admin_id'])) {
        addAdminAlert('security', "Session timeout - automatic logout due to inactivity", $_SESSION['admin_id']);
    }
    session_unset();
    session_destroy();
    header("Location: index.php?timeout=1");
    exit();
}

if (isset($_SESSION['admin_username'])) {
    $_SESSION['LAST_ACTIVITY'] = time();
}

if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} else if (time() - $_SESSION['CREATED'] > 300) {
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}

// ------------------ PHPMailer Setup ------------------ //
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once '../phpmailer/src/Exception.php';
require_once '../phpmailer/src/PHPMailer.php';
require_once '../phpmailer/src/SMTP.php';

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

// ------------------ TEXT CAPTCHA FUNCTIONS ------------------ //
function generateTextCaptcha() {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    $captcha_code = '';
    $length = 6;
    for ($i = 0; $i < $length; $i++) {
        $captcha_code .= $characters[rand(0, strlen($characters) - 1)];
    }
    $_SESSION['captcha_code'] = $captcha_code;
    return $captcha_code;
}

// ------------------ EMAIL SENDING FUNCTION ------------------ //
function sendVerificationEmail($user_email, $username, $code) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'shahinhossen37@gmail.com';  
        $mail->Password   = 'eozw pcwe onoh rrct';  
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->Timeout    = 30;

        $mail->setFrom('shahinhossen37@gmail.com', 'Admin Security Team');
        $mail->addAddress($user_email, $username);
        $mail->addReplyTo('shahinhossen37@gmail.com', 'Admin Security Team');

        $mail->isHTML(true);
        $mail->Subject = 'Admin Login Verification Code';
        
        $message = "
        <html>
        <head>
        <title>Verification Code</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9; }
            .header { text-align: center; margin-bottom: 30px; }
            .code { background-color: #f0f0f0; padding: 15px; text-align: center; font-size: 28px; font-weight: bold; letter-spacing: 5px; margin: 20px 0; border-radius: 5px; color: #4361ee; }
            .footer { margin-top: 30px; font-size: 12px; color: #777; }
        </style>
        </head>
        <body>
        <div class='container'>
            <div class='header'><h2 style='color: #4361ee;'>Admin Login Verification</h2></div>
            <p>Hello $username,</p>
            <p>Your verification code for admin login is:</p>
            <div class='code'>$code</div>
            <p>This code will expire in 10 minutes.</p>
            <p>If you did not request this code, please ignore this email or contact your system administrator.</p>
            <div class='footer'><p>Best regards,<br>Admin Security Team</p></div>
        </div>
        </body>
        </html>
        ";
        
        $mail->Body = $message;
        $mail->AltBody = "Hello $username,\n\nYour verification code for admin login is: $code\n\nThis code will expire in 10 minutes.\n\nIf you did not request this code, please ignore this email or contact your system administrator.\n\nBest regards,\nAdmin Security Team";

        if ($mail->send()) {
            error_log("Email sent successfully to $user_email");
            return true;
        } else {
            error_log("Email sending failed to $user_email");
            return false;
        }
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// ------------------ ADMIN ALERT SYSTEM ------------------ //
function addAdminAlert($type, $message, $admin_id = null) {
    global $conn;
    $alert_id = uniqid('alert_', true);
    $timestamp = time();
    $ip_address = getRealIP();

    $stmt = $conn->prepare("INSERT INTO admin_alerts (alert_id, type, message, timestamp, ip_address, admin_id) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        error_log("SQL Prepare Error: " . $conn->error);
        return false;
    }
    $stmt->bind_param("sssisi", $alert_id, $type, $message, $timestamp, $ip_address, $admin_id);

    if ($stmt->execute()) {
        $stmt->close();
        if (!isset($_SESSION['recent_alerts'])) {
            $_SESSION['recent_alerts'] = [];
        }
        $alert_data = [
            'id' => $alert_id,
            'type' => $type,
            'message' => $message,
            'timestamp' => $timestamp,
            'ip_address' => $ip_address,
            'read' => false
        ];
        array_unshift($_SESSION['recent_alerts'], $alert_data);
        if (count($_SESSION['recent_alerts']) > 10) {
            array_pop($_SESSION['recent_alerts']);
        }
        return $alert_data;
    } else {
        error_log("SQL Execute Error: " . $stmt->error);
        $stmt->close();
        return false;
    }
}

function sendSecurityAlertEmail($user_email, $username, $alert_type, $ip_address, $timestamp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'shahinhossen37@gmail.com';  
        $mail->Password   = 'lvew ddle vvar chwt';  
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('shahinhossen37@gmail.com', 'Security Alert System');
        $mail->addAddress($user_email, $username);
        $mail->isHTML(true);
        
        if ($alert_type === 'lockout') {
            $mail->Subject = 'Security Alert: Account Lockout Detected';
            $message = "
            <html>
            <head><style>body{font-family:Arial,sans-serif;}.container{max-width:600px;margin:0 auto;padding:20px;}.alert{background:#fff3cd;border:1px solid #ffeaa7;padding:15px;border-radius:5px;}</style></head>
            <body>
            <div class='container'>
                <h2 style='color:#dc3545;'>🔒 Security Alert: Account Lockout</h2>
                <div class='alert'>
                    <p><strong>Hello $username,</strong></p>
                    <p>Your admin account has been temporarily locked due to multiple failed login attempts.</p>
                    <p><strong>Details:</strong></p>
                    <ul>
                        <li>IP Address: $ip_address</li>
                        <li>Time: " . date('Y-m-d H:i:s', $timestamp) . "</li>
                        <li>Lockout Duration: 2 minutes</li>
                    </ul>
                    <p>If this was you, please wait 2 minutes and try again.</p>
                    <p>If this wasn't you, please contact your system administrator immediately.</p>
                </div>
                <p><small>This is an automated security alert.</small></p>
            </div>
            </body>
            </html>
            ";
        } else if ($alert_type === 'failed_attempts') {
            $mail->Subject = 'Security Alert: Multiple Failed Login Attempts';
            $message = "
            <html>
            <body>
            <h2>Security Alert: Failed Login Attempts</h2>
            <p>Hello $username,</p>
            <p>We detected multiple failed login attempts for your admin account.</p>
            <p><strong>IP Address:</strong> $ip_address</p>
            <p><strong>Time:</strong> " . date('Y-m-d H:i:s', $timestamp) . "</p>
            <p>If this wasn't you, please secure your account immediately.</p>
            </body>
            </html>
            ";
        }
        $mail->Body = $message;
        return $mail->send();
    } catch (Exception $e) {
        error_log("Security Alert Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

// ------------------ LOGIN LOGIC ------------------ //
if (isset($_SESSION['admin_username'])) {
    header("Location: dashboard.php");
    exit();
}

// Initialize variables
$show_captcha = false;
$show_verification = false;
$error = '';
$email_status = '';
$email_message = '';
$remaining_attempts = $max_login_attempts;
$lockout_status = null;
$specific_lockout_message = '';
$try_different_user_message = '';

// Generate CAPTCHA code
if (!isset($_SESSION['captcha_code'])) {
    $current_captcha = generateTextCaptcha();
} else {
    $current_captcha = $_SESSION['captcha_code'];
}

// Initialize session alerts
if (!isset($_SESSION['recent_alerts'])) {
    $_SESSION['recent_alerts'] = [];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle CAPTCHA refresh
    if (isset($_POST['refresh_captcha'])) {
        $current_captcha = generateTextCaptcha();
        $_SESSION['captcha_code'] = $current_captcha;
    }
    // Handle login form submission
    else if (!isset($_POST['verification_code']) && !isset($_POST['resend_code'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $entered_captcha = isset($_POST['captcha_code']) ? trim($_POST['captcha_code']) : '';
        
        $client_ip = getRealIP();
        
        // Check account lockout status FIRST - only for this username
        $lockout_status = checkAccountLockout($username, $client_ip);
        
        if ($lockout_status['locked']) {
            $remaining_minutes = ceil($lockout_status['remaining_time'] / 60);
            $error = "🔒 Account locked. Try again in " . ceil($lockout_status['remaining_time'] / 60) . " minutes.";
            $specific_lockout_message = "⚠️ The account <strong>'$username'</strong> is temporarily locked for security reasons.";
            $try_different_user_message = "💡 Try logging in with a different username or wait 2 minutes.";
            $show_captcha = true;
            
            // Send security alert email
            $stmt = $conn->prepare("SELECT id, email FROM admin WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $admin = $result->fetch_assoc();
                sendSecurityAlertEmail($admin['email'], $username, 'lockout', $client_ip, time());
            }
            $stmt->close();
            
            addAdminAlert('security', "Account lockout triggered for username: $username from IP: $client_ip");
        }
        // Validate CAPTCHA if shown
        else if ($show_captcha) {
            if (!isset($_SESSION['captcha_code']) || empty($_SESSION['captcha_code'])) {
                $error = "CAPTCHA session expired. Please refresh the page.";
                $show_captcha = true;
                $current_captcha = generateTextCaptcha();
                $_SESSION['captcha_code'] = $current_captcha;
            }
            else if (strtoupper($entered_captcha) !== strtoupper($_SESSION['captcha_code'])) {
                $error = "Invalid CAPTCHA code. Please try again.";
                $show_captcha = true;
                $current_captcha = generateTextCaptcha();
                $_SESSION['captcha_code'] = $current_captcha;
                
                // Track failed CAPTCHA attempt
                $attempt_result = trackLoginAttempt($username, $client_ip, false);
                $remaining_attempts = getRemainingAttempts($username, $client_ip);
                
                addAdminAlert('security', "Failed CAPTCHA attempt for username: $username from IP: $client_ip");
            } else {
                // CAPTCHA passed
                $show_captcha = false;
            }
        }
        
        // If no lockout and no CAPTCHA error, proceed with login verification
        if (empty($error) && !$lockout_status['locked']) {
            $stmt = $conn->prepare("SELECT id, username, password, two_factor_enabled, email, role_id FROM admin WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $admin = $result->fetch_assoc();

                if ($password === $admin['password']) {
                    // SUCCESSFUL LOGIN - Reset attempts for this username
                    trackLoginAttempt($username, $client_ip, true);
                    
                    if ($admin['two_factor_enabled'] == 1) {
                        $verification_code = rand(100000, 999999);
                        $_SESSION['verification_code'] = $verification_code;
                        $_SESSION['temp_admin_id'] = $admin['id'];
                        $_SESSION['temp_username'] = $username;
                        $_SESSION['temp_email'] = $admin['email'];
                        $_SESSION['temp_role_id'] = $admin['role_id'];
                        
                        $admin_email = $admin['email'];
                        $email_sent = sendVerificationEmail($admin_email, $username, $verification_code);
                        
                        if ($email_sent) {
                            addAdminAlert('security', "2FA code sent to email for username: $username", $admin['id']);
                            $email_status = "success";
                            $email_message = "A verification code has been sent to your email address ($admin_email).";
                        } else {
                            addAdminAlert('danger', "Failed to send 2FA code for username: $username", $admin['id']);
                            $email_status = "danger";
                            $email_message = "Failed to send verification code to $admin_email. Please contact system administrator.";
                        }
                        
                        $show_verification = true;
                    } else {
                        // RBAC INTEGRATION - Initialize RBAC session
                        $rbac = CompleteRBAC::initializeSession($conn, $admin['id'], $admin['username'], $admin['email']);
                        
                        addAdminAlert('success', "Successful login for username: $username - {$rbac->getAdminType()}", $admin['id']);
                        header("Location: dashboard.php");
                        exit();
                    }
                } else {
                    // FAILED LOGIN - Track attempt for this username
                    $attempt_result = trackLoginAttempt($username, $client_ip, false);
                    
                    // Check if this attempt caused lockout
                    if (isset($attempt_result['locked']) && $attempt_result['locked']) {
                        $error = "🔒 " . $attempt_result['message'];
                        $specific_lockout_message = "⚠️ The account <strong>'$username'</strong> is temporarily locked for security reasons.";
                        $try_different_user_message = "💡 Try logging in with a different username or wait 2 minutes.";
                        $show_captcha = true;
                        
                        // Send immediate lockout alert
                        sendSecurityAlertEmail($admin['email'], $username, 'lockout', $client_ip, time());
                        addAdminAlert('security', "Account lockout triggered for username: $username (5 failed attempts)");
                    } else {
                        $remaining_attempts = $max_login_attempts - $attempt_result['attempts'];
                        
                        // Only show "remaining attempts" message if not locked
                        if ($remaining_attempts > 0) {
                            $error = "Invalid username or password. ";
                            $error .= "Remaining attempts: " . $remaining_attempts;
                        }
                        
                        // Show CAPTCHA after 2 failed attempts
                        if ($attempt_result['attempts'] >= $captcha_after_attempts) {
                            $show_captcha = true;
                            $current_captcha = generateTextCaptcha();
                            $_SESSION['captcha_code'] = $current_captcha;
                        }
                        
                        // Send security alert after 3 failed attempts
                        if ($attempt_result['attempts'] >= 3) {
                            sendSecurityAlertEmail($admin['email'], $username, 'failed_attempts', $client_ip, time());
                        }
                        
                        addAdminAlert('security', "Failed login attempt for username: $username (Attempt: {$attempt_result['attempts']})");
                    }
                }
            } else {
                // Username not found - Track attempt for this username
                $attempt_result = trackLoginAttempt($username, $client_ip, false);
                
                // Check if this attempt caused lockout
                if (isset($attempt_result['locked']) && $attempt_result['locked']) {
                    $error = "🔒 " . $attempt_result['message'];
                    $specific_lockout_message = "⚠️ The account <strong>'$username'</strong> is temporarily locked for security reasons.";
                    $try_different_user_message = "💡 Try logging in with a different username or wait 2 minutes.";
                    $show_captcha = true;
                    addAdminAlert('security', "Account lockout triggered for unknown username: $username (5 failed attempts)");
                } else {
                    $remaining_attempts = $max_login_attempts - $attempt_result['attempts'];
                    
                    // Only show "remaining attempts" message if not locked
                    if ($remaining_attempts > 0) {
                        $error = "Invalid username or password. ";
                        $error .= "Remaining attempts: " . $remaining_attempts;
                    }
                    
                    // Show CAPTCHA after 2 failed attempts
                    if ($attempt_result['attempts'] >= $captcha_after_attempts) {
                        $show_captcha = true;
                        $current_captcha = generateTextCaptcha();
                        $_SESSION['captcha_code'] = $current_captcha;
                    }
                    
                    addAdminAlert('security', "Failed login attempt for unknown username: $username (Attempt: {$attempt_result['attempts']})");
                }
            }
            $stmt->close();
        }
    }
}

// ------------------ 2FA VERIFICATION ------------------ //
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verification_code'])) {
    $entered_code = trim($_POST['verification_code']);

    if (isset($_SESSION['verification_code']) && $entered_code == $_SESSION['verification_code']) {
        // RBAC INTEGRATION - Initialize RBAC session after 2FA verification
        $rbac = CompleteRBAC::initializeSession($conn, $_SESSION['temp_admin_id'], $_SESSION['temp_username'], $_SESSION['temp_email']);
        
        addAdminAlert('success', "Successful login with 2FA - {$rbac->getAdminType()}", $_SESSION['temp_admin_id']);

        unset($_SESSION['verification_code']);
        unset($_SESSION['temp_admin_id']);
        unset($_SESSION['temp_username']);
        unset($_SESSION['temp_email']);
        unset($_SESSION['temp_role_id']);

        header("Location: dashboard.php");
        exit();
    } else {
        $verification_error = "Invalid verification code";
        addAdminAlert('danger', "Failed 2FA attempt for username: {$_SESSION['temp_username']}");
        $show_verification = true;
    }
}

// ------------------ RESEND VERIFICATION CODE ------------------ //
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['resend_code'])) {
    if (isset($_SESSION['temp_admin_id']) && isset($_SESSION['temp_username'])) {
        $admin_id = $_SESSION['temp_admin_id'];
        
        $stmt = $conn->prepare("SELECT id, username, email FROM admin WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            
            $verification_code = rand(100000, 999999);
            $_SESSION['verification_code'] = $verification_code;
            
            $admin_email = $admin['email'];
            $email_sent = sendVerificationEmail($admin_email, $admin['username'], $verification_code);
            
            if ($email_sent) {
                addAdminAlert('info', "2FA code resent to email for username: {$admin['username']}", $admin['id']);
                $resend_status = "success";
                $resend_message = "A new verification code has been sent to your email address ($admin_email).";
            } else {
                addAdminAlert('danger', "Failed to resend 2FA code to email for username: {$admin['username']}", $admin['id']);
                $resend_status = "danger";
                $resend_message = "Failed to send verification code to $admin_email. Please contact system administrator.";
            }
        }
        $stmt->close();
    }
}

// Get current lockout status for display
if (isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $client_ip = getRealIP();
    $lockout_status = checkAccountLockout($username, $client_ip);
    $remaining_attempts = getRemainingAttempts($username, $client_ip);
} else {
    $remaining_attempts = $max_login_attempts;
}

// Get alerts for display
$recent_alerts = isset($_SESSION['recent_alerts']) ? $_SESSION['recent_alerts'] : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    
    <!-- Bootstrap & other includes remain the same -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #3498db;
            --light: #f8f9fa;
            --dark: #212529;
        }
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
        }
        
        .main-container {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 30px;
            text-align: center;
            border-bottom: none;
        }
        
        .card-body {
            padding: 40px;
        }
        
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.4);
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .input-icon .form-control {
            padding-left: 45px;
        }
        
        .avatar-container {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
        }
        
        .required-field::after {
            content: "*";
            color: #dc3545;
            margin-left: 4px;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .alert i {
            font-size: 1.2rem;
            margin-right: 10px;
        }
        
        .attempts-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            border-left: 4px solid #ffc107;
        }
        
        .lockout-alert {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #dc3545;
        }
        
        .specific-lockout-message {
            background: #e7f3ff;
            border: 1px solid #b3d7ff;
            color: #004085;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 10px;
            font-size: 0.9rem;
            border-left: 4px solid #007bff;
        }
        
        .try-different-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            border-left: 4px solid #28a745;
        }
        
        .verification-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .code-inputs {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .code-input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .code-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
            outline: none;
        }
        
        /* TEXT CAPTCHA Styles */
        .captcha-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }

        .captcha-display {
            background: linear-gradient(135deg, #4361ee, #3f37c9);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 3px;
            margin-bottom: 15px;
            border: 2px solid #e2e8f0;
            font-family: 'Courier New', monospace;
        }

        .captcha-refresh-btn {
            border: 2px solid var(--primary);
            color: var(--primary);
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s ease;
            background: white;
            cursor: pointer;
            font-size: 14px;
            width: 100%;
        }

        .captcha-refresh-btn:hover {
            background-color: var(--primary);
            color: white;
        }

        .resend-code {
            text-align: center;
            margin-top: 15px;
        }

        .security-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            border-radius: 8px;
            padding: 10px 15px;
            margin-top: 15px;
            font-size: 0.85rem;
        }

        /* Admin Alerts Styles */
        .admin-alerts-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            max-width: 400px;
        }
        
        .admin-alert {
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            display: flex;
            align-items: flex-start;
            animation: slideIn 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .admin-alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
        }
        
        .admin-alert.security {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }
        
        .admin-alert.security .alert-icon {
            color: #dc3545;
        }
        
        .admin-alert.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-left: 5px solid #0dcaf0;
        }
        
        .admin-alert.info .alert-icon {
            color: #0dcaf0;
        }
        
        .admin-alert.warning {
            background-color: #fff3cd;
            color: #856404;
            border-left: 5px solid #ffc107;
        }
        
        .admin-alert.warning .alert-icon {
            color: #ffc107;
        }
        
        .admin-alert.success {
            background-color: #d4edda;
            color: #155724;
            border-left: 5px solid #198754;
        }
        
        .admin-alert.success .alert-icon {
            color: #198754;
        }
        
        .admin-alert.danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }
        
        .admin-alert.danger .alert-icon {
            color: #dc3545;
        }
        
        .alert-icon {
            font-size: 1.5rem;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .alert-content {
            flex-grow: 1;
        }
        
        .alert-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .alert-message {
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .alert-details {
            font-size: 0.75rem;
            opacity: 0.7;
            display: flex;
            justify-content: space-between;
        }
        
        .alert-close {
            background: none;
            border: none;
            font-size: 1.2rem;
            opacity: 0.7;
            cursor: pointer;
            padding: 0;
            margin-left: 10px;
            flex-shrink: 0;
        }
        
        .alert-close:hover {
            opacity: 1;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        .alert-slide-out {
            animation: slideOut 0.3s ease forwards;
        }

        @media (max-width: 768px) {
            .card-body {
                padding: 30px;
            }
            
            .code-input {
                width: 40px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .captcha-section {
                padding: 15px;
            }
            
            .captcha-display {
                font-size: 20px;
                padding: 12px;
            }
            
            .admin-alerts-container {
                top: 10px;
                right: 10px;
                left: 10px;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Alerts Container -->
    <div class="admin-alerts-container" id="adminAlertsContainer">
        <?php foreach ($recent_alerts as $alert): ?>
            <div class="admin-alert <?= $alert['type'] ?>" data-id="<?= $alert['id'] ?>">
                <i class="fas 
                    <?= $alert['type'] === 'security' ? 'fa-shield-alt' : '' ?>
                    <?= $alert['type'] === 'success' ? 'fa-check-circle' : '' ?>
                    <?= $alert['type'] === 'warning' ? 'fa-exclamation-triangle' : '' ?>
                    <?= $alert['type'] === 'danger' ? 'fa-exclamation-circle' : '' ?>
                    <?= $alert['type'] === 'info' ? 'fa-info-circle' : '' ?>
                    alert-icon"></i>
                <div class="alert-content">
                    <div class="alert-title"><?= ucfirst($alert['type']) ?> Alert</div>
                    <div class="alert-message"><?= htmlspecialchars($alert['message']) ?></div>
                    <div class="alert-details">
                        <span class="alert-time"><?= date('H:i:s', $alert['timestamp']) ?></span>
                        <?php if ($alert['ip_address']): ?>
                            <span class="alert-ip">IP: <?= $alert['ip_address'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <button class="alert-close" onclick="closeAlert('<?= $alert['id'] ?>')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="main-container">
        <div class="card">
            <div class="card-header">
                <h2 class="mb-0"><i class="fas fa-lock me-2"></i>Admin Portal</h2>
            </div>
            
            <div class="card-body">
                <div class="avatar-container">
                    <div class="avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>

                <!-- Session Timeout Message -->
                <?php if (isset($_GET['timeout']) && $_GET['timeout'] == 1): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-clock me-2"></i> Your session has expired due to inactivity. Please login again.
                    </div>
                <?php endif; ?>

                <!-- Specific lockout message -->
                <?php if (!empty($specific_lockout_message)): ?>
                    <div class="specific-lockout-message">
                        <i class="fas fa-user-lock me-2"></i>
                        <?= $specific_lockout_message ?>
                    </div>
                <?php endif; ?>

                <!-- Try different user message -->
                <?php if (!empty($try_different_user_message)): ?>
                    <div class="try-different-message">
                        <i class="fas fa-lightbulb me-2"></i>
                        <?= $try_different_user_message ?>
                    </div>
                <?php endif; ?>

                <!-- Account Lockout Message -->
                <?php if (isset($lockout_status) && $lockout_status['locked']): ?>
                    <div class="lockout-alert">
                        <i class="fas fa-lock me-2"></i>
                        <strong>Account Temporarily Locked</strong><br>
                        Too many failed login attempts. Please try again in <?= ceil($lockout_status['remaining_time'] / 60) ?> minutes.
                    </div>
                <?php endif; ?>

                <!-- Attempts Warning (ONLY show when NOT locked and attempts remaining) -->
                <?php if (isset($remaining_attempts) && $remaining_attempts < $max_login_attempts && $remaining_attempts > 0 && (!isset($lockout_status) || !$lockout_status['locked'])): ?>
                    <div class="attempts-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Security Notice:</strong> You have <?= $remaining_attempts ?> attempt(s) remaining before account lockout.
                    </div>
                <?php endif; ?>

                <?php if (isset($error) && !empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($verification_error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($verification_error) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($show_verification) && $show_verification): ?>
                    <!-- Verification Code Form -->
                    <h4 class="text-center mb-4">Two-Step Verification</h4>
                    
                    <?php if (isset($email_status) && $email_status === "success"): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($email_message) ?>
                        </div>
                    <?php elseif (isset($email_status) && $email_status === "danger"): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($email_message) ?>
                        </div>
                    <?php else: ?>
                        <div class="verification-info">
                            <i class="fas fa-envelope-open-text"></i>
                            <p class="mb-0">For security purposes, a verification code has been sent to your email address.</p>
                            <p class="mb-0 text-muted">Please check your inbox and enter the code below.</p>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($resend_status) && $resend_status === "success"): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($resend_message) ?>
                        </div>
                    <?php elseif (isset($resend_status) && $resend_status === "danger"): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($resend_message) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-4">
                            <label class="form-label required-field">Enter Verification Code</label>
                            <div class="code-inputs">
                                <input type="text" name="verification_code" maxlength="6" class="form-control text-center" required 
                                       pattern="[0-9]{6}" title="Please enter a 6-digit code" id="verificationInput">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check-circle me-2"></i> Verify & Continue
                        </button>
                    </form>

                    <div class="resend-code">
                        <p>Didn't receive the code? 
                            <form method="POST" action="" style="display: inline;">
                                <button type="submit" name="resend_code" class="btn btn-link p-0 m-0">Resend Code</button>
                            </form>
                        </p>
                    </div>

                <?php else: ?>
                    <!-- Login Form with ACCOUNT LOCKOUT PROTECTION -->
                    <h4 class="text-center mb-4">Admin Login</h4>
                    
                    <form method="POST" action="" id="loginForm">
                        <div class="mb-4">
                            <label for="username" class="form-label required-field">Username</label>
                            <div class="input-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" name="username" class="form-control" required 
                                       value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                                       <?= (isset($lockout_status) && $lockout_status['locked']) ? 'readonly' : '' ?>>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label required-field">Password</label>
                            <div class="input-icon">
                                <i class="fas fa-key"></i>
                                <input type="password" name="password" class="form-control" required 
                                       <?= (isset($lockout_status) && $lockout_status['locked']) ? 'readonly' : '' ?>>
                            </div>
                        </div>

                        <!-- TEXT CAPTCHA Section -->
                        <?php if ($show_captcha): ?>
                        <div class="captcha-section">
                            <label for="captcha_code" class="form-label required-field">Enter CAPTCHA Code</label>
                            
                            <div class="captcha-display" id="captchaDisplay">
                                <?= htmlspecialchars($current_captcha) ?>
                            </div>
                            
                            <button type="submit" name="refresh_captcha" class="captcha-refresh-btn">
                                <i class="fas fa-redo me-1"></i> Generate New CAPTCHA
                            </button>
                            
                            <div class="input-icon mt-3">
                                <i class="fas fa-shield-alt"></i>
                                <input type="text" name="captcha_code" class="form-control" maxlength="6" 
                                       placeholder="Enter the code shown above" required title="Please enter the code shown above"
                                       value="<?= isset($_POST['captcha_code']) ? htmlspecialchars($_POST['captcha_code']) : '' ?>">
                            </div>
                            <small class="text-muted mt-2 d-block">
                                <i class="fas fa-info-circle me-1"></i> Enter the 6-character code shown above (case insensitive)
                            </small>
                        </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary" <?= (isset($lockout_status) && $lockout_status['locked']) ? 'disabled' : '' ?>>
                            <i class="fas fa-sign-in-alt me-2"></i> 
                            <?= (isset($lockout_status) && $lockout_status['locked']) ? 'Account Locked' : 'Login' ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

<script>
    // Admin alerts functionality
    function closeAlert(alertId) {
        const alertEl = document.querySelector(`.admin-alert[data-id="${alertId}"]`);
        if (alertEl) {
            alertEl.classList.add('alert-slide-out');
            setTimeout(() => {
                if (alertEl.parentNode) {
                    alertEl.remove();
                }
            }, 300);
        }
    }

    // Auto-close alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.admin-alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                closeAlert(alert.dataset.id);
            }, 5000);
        });
    });

    // CAPTCHA input auto-alphanumeric only
    document.addEventListener('DOMContentLoaded', function() {
        const captchaInput = document.querySelector('input[name="captcha_code"]');
        if (captchaInput) {
            captchaInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^a-zA-Z0-9]/g, '');
            });
        }

        // Auto-focus on the first input field
        const firstInput = document.querySelector('input[type="text"]');
        if (firstInput && !firstInput.readOnly) firstInput.focus();
        
        // For verification code input, select the entire input when focused
        const codeInput = document.querySelector('input[name="verification_code"]');
        if (codeInput) {
            codeInput.addEventListener('focus', function() {
                this.select();
            });
        }

        // Handle account lockout countdown
        <?php if (isset($lockout_status) && $lockout_status['locked']): ?>
        let lockoutTime = <?= $lockout_status['remaining_time'] ?>;
        
        function updateLockoutTimer() {
            if (lockoutTime <= 0) {
                location.reload();
                return;
            }
            
            const minutes = Math.floor(lockoutTime / 60);
            const seconds = lockoutTime % 60;
            
            // Update countdown display if there is one
            const countdownEl = document.querySelector('.lockout-alert');
            if (countdownEl) {
                const messageEl = countdownEl.querySelector('strong');
                if (messageEl) {
                    messageEl.innerHTML = `Account Temporarily Locked (${minutes}:${seconds.toString().padStart(2, '0')})`;
                }
            }
            
            lockoutTime--;
            setTimeout(updateLockoutTimer, 1000);
        }
        
        updateLockoutTimer();
        <?php endif; ?>
    });

    // Auto-clear CAPTCHA input when page loads
    window.addEventListener('load', function() {
        const captchaInput = document.querySelector('input[name="captcha_code"]');
        if (captchaInput && !captchaInput.value) {
            captchaInput.value = '';
        }
    });
</script>
</body>
</html>