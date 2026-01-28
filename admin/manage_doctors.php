<?php
// Start session at the very beginning
session_start();

// Check if user is logged in, redirect to login if not
if (!isset($_SESSION['admin_username']) || !isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include_once('../dbconn.php');

// Initialize Data Logger
require_once 'data_logger.php';

// Check if session variables exist before using them
$admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 0;
$admin_username = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'System';

$dataLogger = new DataAccessLogger($conn, $admin_id, $admin_username);

// Log doctors page access
$dataLogger->logView('doctors', null, 'Doctors management page accessed');

// Example: When viewing a specific doctor
if (isset($_GET['view_doctor'])) {
    $doctor_id = intval($_GET['view_doctor']);
    $dataLogger->logView('doctors', $doctor_id, 'Viewed doctor details');
}

// Example: When updating a doctor
if (isset($_POST['action']) && $_POST['action'] == 'update_doctor') {
    $doctor_id = intval($_POST['doctor_id']);
    
    // Get old data first
    $stmt = $conn->prepare("SELECT * FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $old_data = $stmt->get_result()->fetch_assoc();
    
    // Perform update...
    $new_data = [
        'name' => $_POST['name'],
        'specialization' => $_POST['specialization'],
        // ... other fields
    ];
    
    // Log the update
    $dataLogger->logUpdate('doctors', $doctor_id, $old_data, $new_data);
}

$doctors = $conn->query("
    SELECT 
        person.nid, person.name, doctor.specialist, 
        person.mobile_no AS phone, person.gender, person.blood
    FROM doctor 
    INNER JOIN person ON doctor.d_nid = person.nid
");

function getGenderText($gender) {
    switch ($gender) {
        case 1: return "Male";
        case 2: return "Female";
        case 3: return "Other";
        default: return "Unknown";
    }
}

function getGenderIcon($gender) {
    switch ($gender) {
        case 1: return "♂";
        case 2: return "♀";
        case 3: return "⚧";
        default: return "❓";
    }
}

// Get stats for dashboard
$doctor_count = $doctors->num_rows;
$specialties_count = $conn->query("SELECT COUNT(DISTINCT specialist) as count FROM doctor")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Management Dashboard</title>
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="session-timeout.js"></script>
    <link rel="stylesheet" href="session-timeout.css">
    
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --sidebar-width: 250px;
        }
        
        * {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: #f5f7fb;
            color: #333;
            display: flex;
            min-height: 100vh;
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
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s;
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
        
        .stat-card.specialty::before {
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
            background: linear-gradient(135deg, #007bff, #00c6ff);
        }
        
        .stat-card.specialty .stat-icon {
            background: linear-gradient(135deg, #17a2b8, #64c3d9);
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
        
        /* Table Container */
        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .table-header {
            padding: 20px;
            border-bottom: 1px solid #f1f1f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-header h3 {
            margin: 0;
            font-weight: 600;
            color: var(--dark);
        }
        
        .table thead {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }
        
        .table th {
            border: none;
            padding: 15px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .table td {
            padding: 15px;
            vertical-align: middle;
            border-color: #f1f1f1;
        }
        
        .table tbody tr {
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-active {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 0;
        }
        
        .empty-state i {
            font-size: 5rem;
            color: #dee2e6;
            margin-bottom: 20px;
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
        }
        
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .table-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
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
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>HealthCare Admin</h3>
        </div>
        <div class="sidebar-menu">
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                <li><a href="#" class="active"><i class="fas fa-user-md"></i> <span>Doctors</span></a></li>
                <li><a href="manage_patients.php"><i class="fas fa-user-injured"></i> <span>Patients</span></a></li>
                <li><a href="manage_hospitals.php"><i class="fas fa-hospital"></i> <span>Hospitals</span></a></li>
                <li><a href="#"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="welcome-text">
                <h2>Doctor Management</h2>
                <p>Manage all doctors in the healthcare system</p>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="add_doctor.php" class="btn btn-success btn-sm">
                    <i class="fas fa-plus-circle"></i> Add New Doctor
                </a>
            </div>
        </div>

        <!-- Success message -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <div>Doctor added successfully!</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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

            <div class="stat-card specialty">
                <div class="stat-icon">
                    <i class="fas fa-stethoscope"></i>
                </div>
                <div class="stat-content">
                    <h3>SPECIALTIES</h3>
                    <h2><?= $specialties_count ?></h2>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i> <span>2 new this month</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Doctors Table -->
        <div class="table-container">
            <div class="table-header">
                <h3>All Doctors</h3>
                <div class="input-group" style="max-width: 300px;">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0" placeholder="Search doctors...">
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>NID</th>
                            <th>Name</th>
                            <th>Specialty</th>
                            <th>Phone</th>
                            <th>Gender</th>
                            <th>Blood Group</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($doctors->num_rows > 0): ?>
                            <?php 
                            // Reset pointer to beginning for result set
                            $doctors->data_seek(0);
                            while ($row = $doctors->fetch_assoc()) { ?>
                                <tr>
                                    <td><span class="fw-bold"><?= htmlspecialchars($row['nid']) ?></span></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                                <i class="fas fa-user-md text-primary"></i>
                                            </div>
                                            <div><?= htmlspecialchars($row['name']) ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?= htmlspecialchars($row['specialist']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['phone']) ?></td>
                                    <td>
                                        <span class="d-flex align-items-center">
                                            <span class="me-1"><?= getGenderIcon($row['gender']) ?></span>
                                            <?= getGenderText($row['gender']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger bg-opacity-10 text-danger">
                                            <?= htmlspecialchars($row['blood']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_doctor.php?nid=<?= urlencode($row['nid']) ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="delete_doctor.php?nid=<?= urlencode($row['nid']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this doctor?')">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="fas fa-user-md"></i>
                                        <h4>No Doctors Found</h4>
                                        <p class="text-muted">There are no doctors in the system yet.</p>
                                        <a href="add_doctor.php" class="btn btn-primary mt-2">
                                            <i class="fas fa-plus-circle"></i> Add First Doctor
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Simple search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[type="text"]');
            const tableRows = document.querySelectorAll('tbody tr');
            
            searchInput.addEventListener('input', function() {
                const searchText = this.value.toLowerCase();
                
                tableRows.forEach(row => {
                    const rowText = row.textContent.toLowerCase();
                    if (rowText.includes(searchText)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>