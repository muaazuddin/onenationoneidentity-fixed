<?php
include_once('../dbconn.php'); 
session_start();

// ✅ RBAC PROTECTION
require_once 'rbac.php';
$rbac = CompleteRBAC::getInstance($conn);
if (!$rbac) {
    header("Location: index.php");
    exit();
}

// ✅ EXISTING SESSION CHECK
if (!isset($_SESSION['admin_username'])) {
    header("Location: index.php");
    exit();
}

// ✅ RBAC PAGE ACCESS CHECK
$current_page = basename($_SERVER['PHP_SELF']);
if (!$rbac->canAccessPage($current_page)) {
    header("Location: index.php");
    exit();
}

// ✅ DATA ACCESS LOGGER - WITH ERROR HANDLING
$dataLogger = null;
try {
    if (file_exists('data_logger.php')) {
        require_once 'data_logger.php';
        $dataLogger = new DataAccessLogger($conn, $_SESSION['admin_id'], $_SESSION['admin_username']);
        // Log dashboard access
        $dataLogger->logView('dashboard', null, 'Dashboard accessed');
    } else {
        error_log("data_logger.php file not found - continuing without data logging");
    }
} catch (Exception $e) {
    error_log("Data logger initialization failed: " . $e->getMessage());
}

function getCount($table) {
    global $conn;
    $res = $conn->query("SELECT COUNT(*) AS count FROM $table");
    return $res->fetch_assoc()['count'];
}

// Get additional stats for the dashboard
$doctor_count = getCount("doctor");
$patient_count = getCount("patient");
$hospital_count = getCount("hospital");



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --doctor-card: linear-gradient(135deg, #007bff, #00c6ff);
            --patient-card: linear-gradient(135deg, #28a745, #88e39e);
            --hospital-card: linear-gradient(135deg, #17a2b8, #64c3d9);
            --sidebar-width: 250px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f5f7fb;
            color: #333;
            min-height: 100vh;
            display: flex;
        }

        /* Session Timeout Notification Styles */
        .timeout-notification {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.3);
            z-index: 10000;
            animation: slideInDown 0.5s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 350px;
            text-align: center;
            font-size: 14px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .timeout-notification:hover {
            transform: translateX(-50%) scale(1.02);
            transition: transform 0.2s ease;
        }

        @keyframes slideInDown {
            from {
                transform: translate(-50%, -100%);
                opacity: 0;
            }
            to {
                transform: translate(-50%, 0);
                opacity: 1;
            }
        }

        @keyframes slideOutUp {
            from {
                transform: translate(-50%, 0);
                opacity: 1;
            }
            to {
                transform: translate(-50%, -100%);
                opacity: 0;
            }
        }

        /* RBAC Specific Styles */
        .disabled-link {
            pointer-events: none;
            opacity: 0.5;
        }

        .disabled-action {
            pointer-events: none;
            background: #f8f9fa !important;
            border: 2px dashed #dee2e6 !important;
        }

        .disabled-action:hover {
            transform: none !important;
            box-shadow: none !important;
            background: #f8f9fa !important;
        }

        .access-badge {
            font-size: 0.7rem;
            padding: 2px 6px;
        }

        .access-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--primary);
            color: white;
            height: 100vh;
            position: fixed;
            padding: 20px 0;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 5px 0 15px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .sidebar-header h3 {
            color: white;
            font-weight: 600;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu ul {
            list-style: none;
            padding: 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            display: block;
            padding: 12px 20px;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 4px solid white;
        }

        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 20px;
            transition: all 0.3s;
            margin-left: var(--sidebar-width);
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .welcome-text h2 {
            color: var(--dark);
            font-weight: 600;
            margin-bottom: 5px;
        }

        .welcome-text p {
            color: #6c757d;
            margin: 0;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
        }

        .stat-card.doctor::before {
            background: var(--primary);
        }

        .stat-card.patient::before {
            background: var(--success);
        }

        .stat-card.hospital::before {
            background: var(--info);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 24px;
            color: white;
        }

        .stat-card.doctor .stat-icon {
            background: var(--doctor-card);
        }

        .stat-card.patient .stat-icon {
            background: var(--patient-card);
        }

        .stat-card.hospital .stat-icon {
            background: var(--hospital-card);
        }

        .stat-content h3 {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .stat-content h2 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 0;
            color: var(--dark);
        }

        .stat-trend {
            display: flex;
            align-items: center;
            margin-top: 10px;
            font-size: 14px;
        }

        .trend-up {
            color: #28a745;
        }

        .trend-down {
            color: #dc3545;
        }

        /* Quick Actions */
        .quick-actions {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 10px;
            color: var(--primary);
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border: none;
            border-radius: 10px;
            padding: 20px 15px;
            text-align: center;
            transition: all 0.3s ease;
            color: var(--dark);
            text-decoration: none;
            position: relative;
        }

        .action-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .action-btn i {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .action-btn span {
            font-size: 14px;
            font-weight: 500;
        }

        .access-tag {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #ffc107;
            color: #000;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.6rem;
            font-weight: 600;
        }

        /* Recent Activity */
        .recent-activity {
            background: white;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 15px 0;
            border-bottom: 1px solid #f1f1f1;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
            color: white;
        }

        .activity-icon.doctor {
            background: var(--primary);
        }

        .activity-icon.patient {
            background: var(--success);
        }

        .activity-icon.hospital {
            background: var(--info);
        }

        .activity-content {
            flex: 1;
        }

        .activity-content h4 {
            font-size: 14px;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .activity-content p {
            font-size: 13px;
            color: #6c757d;
            margin: 0;
        }

        .activity-time {
            font-size: 12px;
            color: #6c757d;
        }

        /* Footer */
        footer {
            background-color: white;
            padding: 15px 25px;
            text-align: center;
            border-radius: 12px;
            margin-top: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        /* Animation for username */
        @keyframes colorChange {
            0% { color: #4361ee; }
            25% { color: #3f37c9; }
            50% { color: #4cc9f0; }
            75% { color: #f72585; }
            100% { color: #4361ee; }
        }

        .animated-username {
            animation: colorChange 5s infinite;
            font-weight: 700;
        }

        /* Alert Badge */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }
            
            .sidebar-header h3, .sidebar-menu span {
                display: none;
            }
            
            .sidebar-menu a {
                text-align: center;
                padding: 15px 10px;
            }
            
            .sidebar-menu i {
                margin-right: 0;
                font-size: 20px;
            }
            
            .main-content {
                margin-left: 70px;
            }

            .timeout-notification {
                min-width: 280px;
                left: 20px;
                right: 20px;
                transform: none;
            }
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .sidebar {
                width: 0;
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
        }
        /* Session Timeout Notification Styles */
.timeout-notification {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    color: white;
    padding: 15px 20px;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
    z-index: 10000;
    animation: slideInDown 0.5s ease;
    display: flex;
    align-items: center;
    gap: 15px;
    min-width: 400px;
    max-width: 500px;
    font-size: 14px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    pointer-events: auto;
}

.timeout-notification .btn-close {
    filter: invert(1);
    opacity: 0.8;
}

.timeout-notification .btn-close:hover {
    opacity: 1;
}

@keyframes slideInDown {
    from {
        transform: translate(-50%, -100%);
        opacity: 0;
    }
    to {
        transform: translate(-50%, 0);
        opacity: 1;
    }
}

@keyframes slideOutUp {
    from {
        transform: translate(-50%, 0);
        opacity: 1;
    }
    to {
        transform: translate(-50%, -100%);
        opacity: 0;
    }
}

/* Responsive design for notifications */
@media (max-width: 768px) {
    .timeout-notification {
        min-width: 300px;
        max-width: 90%;
        left: 50%;
        transform: translateX(-50%);
        padding: 12px 15px;
        font-size: 13px;
    }
    
    .timeout-notification div {
        flex: 1;
    }
}
    </style>
</head>
<body>




    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>HealthCare Admin</h3>
            <small class="text-white-50"><?php echo $rbac->getAdminType(); ?></small>
        </div>
        <div class="sidebar-menu">
            <ul>
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                
                <?php if ($rbac->isEvenAdmin()): ?>
                <!-- Even ID Admins - Full Access -->
                <li><a href="manage_doctors.php"><i class="fas fa-user-md"></i> <span>Doctors</span></a></li>
                <li><a href="manage_patients.php"><i class="fas fa-user-injured"></i> <span>Patients</span></a></li>
                <li><a href="manage_hospitals.php"><i class="fas fa-hospital"></i> <span>Hospitals</span></a></li>
                <li><a href="#"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
                <?php else: ?>
                <!-- Odd ID Admins - Dashboard Only -->
                <li><a href="#" class="text-white-50 disabled-link" onclick="showAccessDenied()"><i class="fas fa-user-md"></i> <span>Doctors</span></a></li>
                <li><a href="#" class="text-white-50 disabled-link" onclick="showAccessDenied()"><i class="fas fa-user-injured"></i> <span>Patients</span></a></li>
                <li><a href="#" class="text-white-50 disabled-link" onclick="showAccessDenied()"><i class="fas fa-hospital"></i> <span>Hospitals</span></a></li>
                <li><a href="#" class="text-white-50 disabled-link" onclick="showAccessDenied()"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
                <li><a href="#" class="text-white-50 disabled-link" onclick="showAccessDenied()"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
                <?php endif; ?>
                
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
            
            <!-- Admin Access Info -->
            <div class="mt-4 p-3 bg-dark bg-opacity-25 rounded">
                <small class="text-white-50 d-block">Admin ID: <?php echo $_SESSION['admin_id']; ?></small>
                <small class="text-white-50 d-block">Access Level: 
                    <span class="badge bg-<?php echo $rbac->isEvenAdmin() ? 'success' : 'warning'; ?>">
                        <?php echo $rbac->isEvenAdmin() ? 'Full Access' : 'Dashboard Only'; ?>
                    </span>
                </small>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="welcome-text">
                <h2>Admin Dashboard</h2>
                <p>Welcome back, <span class="animated-username"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span> 
                   <small class="text-muted">(<?php echo $rbac->getAdminType(); ?>)</small>
                </p>
                <?php if (!$rbac->isEvenAdmin()): ?>
                <div class="alert alert-warning alert-dismissible fade show mt-2 py-2" role="alert" style="font-size: 0.85rem;">
                    <i class="fas fa-info-circle me-1"></i>
                    <strong>Restricted Access:</strong> You can only access Dashboard. Contact administrator for full access.
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
            </div>
            <div class="header-actions d-flex align-items-center">
                <!-- admin alert section -->
                <div class="dropdown me-4 position-relative">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" 
                       id="alertDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell fs-4 text-dark"></i>
                        <span id="alertCount" class="notification-badge" style="display:none;">0</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="alertDropdown" id="alertList" style="width: 300px;">
                        <li><span class="dropdown-item-text text-muted">No alerts</span></li>
                    </ul>
                </div>
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
            </div>
        </div>

        <!-- Access Information Card -->
        <?php if (!$rbac->isEvenAdmin()): ?>
        <div class="access-info-card">
            <div class="d-flex align-items-center">
                <i class="fas fa-lock me-3 fs-2"></i>
                <div>
                    <h5 class="mb-1">Restricted Access Mode</h5>
                    <p class="mb-0">Your admin ID (<?php echo $_SESSION['admin_id']; ?>) has dashboard-only access. Contact system administrator for full privileges.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card doctor">
                <div class="stat-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="stat-content">
                    <h3>TOTAL DOCTORS</h3>
                    <h2><?= $doctor_count ?></h2>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i> <span>5% from last month</span>
                    </div>
                </div>
            </div>

            <div class="stat-card patient">
                <div class="stat-icon">
                    <i class="fas fa-user-injured"></i>
                </div>
                <div class="stat-content">
                    <h3>TOTAL PATIENTS</h3>
                    <h2><?= $patient_count ?></h2>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i> <span>12% from last month</span>
                    </div>
                </div>
            </div>

            <div class="stat-card hospital">
                <div class="stat-icon">
                    <i class="fas fa-hospital"></i>
                </div>
                <div class="stat-content">
                    <h3>TOTAL HOSPITALS</h3>
                    <h2><?= $hospital_count ?></h2>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i> <span>2% from last month</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3 class="section-title"><i class="fas fa-bolt"></i> Quick Actions</h3>
            <div class="action-buttons">
                <?php if ($rbac->isEvenAdmin()): ?>
                <!-- Even ID Admins - Full Access Actions -->
                <a href="manage_doctors.php" class="action-btn">
                    <i class="fas fa-user-md"></i>
                    <span>Manage Doctors</span>
                </a>
                <a href="manage_patients.php" class="action-btn">
                    <i class="fas fa-user-injured"></i>
                    <span>Manage Patients</span>
                </a>
                <a href="manage_hospitals.php" class="action-btn">
                    <i class="fas fa-hospital"></i>
                    <span>Manage Hospitals</span>
                </a>
                <a href="#" class="action-btn">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add New User</span>
                </a>
                <a href="reports.php" class="action-btn">
                    <i class="fas fa-chart-bar"></i>
                    <span>View Reports</span>
                </a>
                <a href="#" class="action-btn">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <?php else: ?>
                <!-- Odd ID Admins - Limited Actions -->
                <a href="#" class="action-btn disabled-action" onclick="showAccessDenied()">
                    <i class="fas fa-user-md text-muted"></i>
                    <span class="text-muted">Manage Doctors</span>
                    <small class="text-warning mt-1">Even ID Only</small>
                    <span class="access-tag">LOCKED</span>
                </a>
                <a href="#" class="action-btn disabled-action" onclick="showAccessDenied()">
                    <i class="fas fa-user-injured text-muted"></i>
                    <span class="text-muted">Manage Patients</span>
                    <small class="text-warning mt-1">Even ID Only</small>
                    <span class="access-tag">LOCKED</span>
                </a>
                <a href="#" class="action-btn disabled-action" onclick="showAccessDenied()">
                    <i class="fas fa-hospital text-muted"></i>
                    <span class="text-muted">Manage Hospitals</span>
                    <small class="text-warning mt-1">Even ID Only</small>
                    <span class="access-tag">LOCKED</span>
                </a>
                <a href="#" class="action-btn disabled-action" onclick="showAccessDenied()">
                    <i class="fas fa-plus-circle text-muted"></i>
                    <span class="text-muted">Add New User</span>
                    <small class="text-warning mt-1">Even ID Only</small>
                    <span class="access-tag">LOCKED</span>
                </a>
                <a href="#" class="action-btn disabled-action" onclick="showAccessDenied()">
                    <i class="fas fa-chart-bar text-muted"></i>
                    <span class="text-muted">View Reports</span>
                    <small class="text-warning mt-1">Even ID Only</small>
                    <span class="access-tag">LOCKED</span>
                </a>
                <a href="#" class="action-btn disabled-action" onclick="showAccessDenied()">
                    <i class="fas fa-cog text-muted"></i>
                    <span class="text-muted">Settings</span>
                    <small class="text-warning mt-1">Even ID Only</small>
                    <span class="access-tag">LOCKED</span>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <!-- <div class="recent-activity">
            <h3 class="section-title"><i class="fas fa-history"></i> Recent Activity</h3>
            <ul class="activity-list">
                <li class="activity-item">
                    <div class="activity-icon doctor">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="activity-content">
                        <h4>New doctor registered</h4>
                        <p>Dr. Sarah Johnson joined the system</p>
                    </div>
                    <div class="activity-time">2 hours ago</div>
                </li>
                <li class="activity-item">
                    <div class="activity-icon patient">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <div class="activity-content">
                        <h4>Patient appointment scheduled</h4>
                        <p>John Doe booked an appointment with Dr. Smith</p>
                    </div>
                    <div class="activity-time">5 hours ago</div>
                </li>
                <li class="activity-item">
                    <div class="activity-icon hospital">
                        <i class="fas fa-hospital"></i>
                    </div>
                    <div class="activity-content">
                        <h4>New hospital added</h4>
                        <p>City General Hospital was added to the system</p>
                    </div>
                    <div class="activity-time">Yesterday</div>
                </li>
                <li class="activity-item">
                    <div class="activity-icon doctor">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="activity-content">
                        <h4>Doctor profile updated</h4>
                        <p>Dr. Michael Brown updated his availability</p>
                    </div>
                    <div class="activity-time">2 days ago</div>
                </li>
            </ul>
        </div>


<-- Data Access Activity -->
<div class="recent-activity mt-4">
    <h3 class="section-title"><i class="fas fa-history"></i> Recent Data Access Activity</h3>
    <ul class="activity-list">
        <?php
        $recent_logs = $dataLogger->getRecentLogs(5);
        foreach ($recent_logs as $log):
            $action_icons = [
                'VIEW' => 'eye',
                'CREATE' => 'plus-circle',
                'UPDATE' => 'edit',
                'DELETE' => 'trash',
                'EXPORT' => 'download',
                'SEARCH' => 'search'
            ];
            $action_colors = [
                'VIEW' => 'primary',
                'CREATE' => 'success',
                'UPDATE' => 'warning',
                'DELETE' => 'danger',
                'EXPORT' => 'info',
                'SEARCH' => 'secondary'
            ];
        ?>
        <li class="activity-item">
            <div class="activity-icon" style="background: var(--<?php echo $action_colors[$log['action_type']]; ?>)">
                <i class="fas fa-<?php echo $action_icons[$log['action_type']]; ?>"></i>
            </div>
            <div class="activity-content">
                <h4>
                    <span class="badge bg-<?php echo $action_colors[$log['action_type']]; ?>">
                        <?php echo $log['action_type']; ?>
                    </span>
                    <?php echo ucfirst($log['table_name']); ?>
                    <?php if ($log['record_id']): ?>
                    <small class="text-muted">(ID: <?php echo $log['record_id']; ?>)</small>
                    <?php endif; ?>
                </h4>
                <p class="mb-1">By: <?php echo htmlspecialchars($log['admin_username']); ?></p>
                <small class="text-muted">IP: <?php echo $log['ip_address']; ?></small>
            </div>
            <div class="activity-time">
                <?php echo date('H:i', strtotime($log['timestamp'])); ?><br>
                <small><?php echo date('M j', strtotime($log['timestamp'])); ?></small>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
</div>




        <!-- Footer -->
        <footer>
            <p class="text-muted mb-0">&copy; 2025 IdentityBasedHealthcareSystem. All rights reserved.</p>
            <small class="text-muted">Access Level: 
                <span class="badge bg-<?php echo $rbac->isEvenAdmin() ? 'success' : 'warning'; ?>">
                    <?php echo $rbac->isEvenAdmin() ? 'Full Access' : 'Dashboard Only'; ?>
                </span>
            </small>
        </footer>
    </div> 
<!-- -->

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
<script>
    // Access denied message for restricted users
    function showAccessDenied() {
        const notification = document.createElement('div');
        notification.className = 'timeout-notification';
        notification.style.background = 'linear-gradient(135deg, #ffc107, #e0a800)';
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-lock" style="font-size: 1.5rem;"></i>
                <div style="flex: 1;">
                    <div style="font-weight: 600; font-size: 15px;">Access Restricted</div>
                    <div style="font-size: 13px; opacity: 0.9;">This feature is available only for Even ID Administrators</div>
                </div>
                <button class="btn-close" style="border: none; background: none; font-size: 12px; opacity: 0.7;" 
                        onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    // Session timeout functionality for dashboard
    document.addEventListener('DOMContentLoaded', function() {
        let warningInterval;
        let logoutTimer;
        let lastActivity = Date.now();
        
        const SESSION_TIMEOUT = 200000; // 2 minutes (300 seconds)
        const WARNING_INTERVAL = 10000; // 10 seconds
        const WARNING_DISPLAY_TIME = 5000; // Show for 5 seconds

        console.log('Session timeout system initialized');
        console.log('Session timeout:', SESSION_TIMEOUT / 1000 + ' seconds');
        console.log('Warning interval:', WARNING_INTERVAL / 1000 + ' seconds');

        function startTimers() {
            console.log('Starting session timeout warnings');
            clearTimers();
            
            // Show first warning after 15 seconds
            setTimeout(showTimeoutWarning, WARNING_INTERVAL);
            
            // Periodic warnings every 15 seconds
            warningInterval = setInterval(showTimeoutWarning, WARNING_INTERVAL);
            
            // Logout timer after 5 minutes
            logoutTimer = setTimeout(logoutUser, SESSION_TIMEOUT);
            
            console.log('Timers started successfully');
        }

        function showTimeoutWarning() {
            const timeElapsed = Date.now() - lastActivity;
            const timeRemaining = SESSION_TIMEOUT - timeElapsed;
            
            if (timeRemaining <= 0) {
                return; // Don't show warning if time is up
            }
            
            const minutesRemaining = Math.floor(timeRemaining / 60000);
            const secondsRemaining = Math.floor((timeRemaining % 60000) / 1000);
            
            const timeDisplay = minutesRemaining > 0 ? 
                `${minutesRemaining}m ${secondsRemaining}s` : 
                `${secondsRemaining}s`;
            
            console.log('Showing timeout warning:', timeDisplay, 'remaining');
            
            // Remove any existing notifications first
            removeExistingNotifications();
            
            const notification = document.createElement('div');
            notification.className = 'timeout-notification';
            
            // Change color based on urgency
            let backgroundColor = 'linear-gradient(135deg, #2196F3, #1976D2)';
            let icon = 'fa-clock';
            
            if (timeRemaining <= 60000) { // Less than 1 minute
                backgroundColor = 'linear-gradient(135deg, #f44336, #d32f2f)';
                icon = 'fa-exclamation-triangle';
            } else if (timeRemaining <= 120000) { // Less than 2 minutes
                backgroundColor = 'linear-gradient(135deg, #FF9800, #F57C00)';
                icon = 'fa-hourglass-half';
            } else if (timeRemaining <= 180000) { // Less than 3 minutes
                backgroundColor = 'linear-gradient(135deg, #FFC107, #FFA000)';
                icon = 'fa-clock';
            }
            
            notification.style.background = backgroundColor;
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fas ${icon}" style="font-size: 1.5rem;"></i>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; font-size: 15px;">Session Timeout Warning</div>
                        <div style="font-size: 13px; opacity: 0.9;">Time remaining: <strong>${timeDisplay}</strong></div>
                    </div>
                    <button class="btn-close btn-close-white" style="border: none; background: none; font-size: 12px; opacity: 0.7;" 
                            onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remove notification after display time
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, WARNING_DISPLAY_TIME);
        }

        function updateActivity() {
            lastActivity = Date.now();
            console.log('Activity detected - session extended');
        }

        function resetTimers() {
            updateActivity();
            console.log('Resetting session timers');
            clearTimers();
            startTimers();
        }

        function clearTimers() {
            if (warningInterval) {
                clearInterval(warningInterval);
                warningInterval = null;
            }
            if (logoutTimer) {
                clearTimeout(logoutTimer);
                logoutTimer = null;
            }
        }

        function removeExistingNotifications() {
            const existingNotifications = document.querySelectorAll('.timeout-notification');
            existingNotifications.forEach(notification => {
                if (notification.parentNode) {
                    notification.remove();
                }
            });
        }

        function logoutUser() {
            console.log('Logging out user due to inactivity');
            clearTimers();
            
            const notification = document.createElement('div');
            notification.className = 'timeout-notification';
            notification.style.background = 'linear-gradient(135deg, #dc3545, #c82333)';
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-sign-out-alt" style="font-size: 1.5rem;"></i>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; font-size: 15px;">Session Expired</div>
                        <div style="font-size: 13px; opacity: 0.9;">Redirecting to login page...</div>
                    </div>
                </div>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                window.location.href = 'index.php?timeout=1';
            }, 2000);
        }

        // Start the timers immediately when page loads
        startTimers();
        
        // User activity detection - VERY LIGHTWEIGHT
        const activityEvents = [
            'mousemove', 'mousedown', 'click', 'scroll', 
            'keypress', 'keydown', 'input', 'touchstart',
            'focus', 'blur'
        ];
        
        activityEvents.forEach(event => {
            document.addEventListener(event, function() {
                // Simply update the last activity timestamp
                lastActivity = Date.now();
            }, { passive: true });
        });

        console.log('Activity listeners attached - no interference with mouse clicks');

        // Show admin access info on page load
        <?php if (!$rbac->isEvenAdmin()): ?>
        console.log('Restricted Admin Access: Dashboard Only');
        <?php else: ?>
        console.log('Full Admin Access: All Pages Available');
        <?php endif; ?>

        // Optional: Add a status indicator for testing
        const statusIndicator = document.createElement('div');
        statusIndicator.style.cssText = `
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 9999;
            pointer-events: none;
        `;
        statusIndicator.innerHTML = 'Session: Active';
        document.body.appendChild(statusIndicator);

        // Update status every second
        setInterval(() => {
            const timeRemaining = SESSION_TIMEOUT - (Date.now() - lastActivity);
            const minutes = Math.floor(timeRemaining / 60000);
            const seconds = Math.floor((timeRemaining % 60000) / 1000);
            statusIndicator.innerHTML = `Session: ${minutes}m ${seconds}s`;
            statusIndicator.style.background = timeRemaining < 60000 ? 'rgba(220,53,69,0.7)' : 'rgba(0,0,0,0.7)';
        }, 1000);

    });

    // Alert system functionality
    function loadAlerts() {
        fetch("fetch_alerts.php")
            .then(res => res.json())
            .then(data => {
                const alertList = document.getElementById("alertList");
                const alertCount = document.getElementById("alertCount");

                alertList.innerHTML = "";
                
                if (data.length === 0) {
                    alertList.innerHTML = "<li><span class='dropdown-item-text text-muted'>No new alerts</span></li>";
                    alertCount.style.display = "none";
                    return;
                }

                data.forEach(alert => {
                    let icon = "fas fa-info-circle text-primary";
                    if (alert.type === "warning") icon = "fas fa-exclamation-triangle text-warning";
                    if (alert.type === "danger") icon = "fas fa-exclamation-octagon text-danger";

                    alertList.innerHTML += `
                        <li>
                            <span class="dropdown-item">
                                <i class="${icon} me-2"></i>
                                ${alert.message} <br>
                                <small class="text-muted">${alert.created_at}</small>
                            </span>
                        </li>
                    `;
                });

                alertCount.innerText = data.length;
                alertCount.style.display = "inline-block";
            })
            .catch(err => console.error('Error loading alerts:', err));
    }

    // Auto refresh alerts every 10 seconds
    setInterval(loadAlerts, 10000);
    loadAlerts();

    // Simple animation for stats counting
    document.addEventListener('DOMContentLoaded', function() {
        const statCards = document.querySelectorAll('.stat-content h2');
        
        statCards.forEach(card => {
            const target = parseInt(card.innerText);
            let current = 0;
            const increment = Math.ceil(target / 50);
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    card.innerText = target;
                    clearInterval(timer);
                } else {
                    card.innerText = current;
                }
            }, 30);
        });
    });
</script>
</body>
</html>