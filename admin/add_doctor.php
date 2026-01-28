<?php
include_once('../dbconn.php');
session_start();

// Redirect if not logged in
if (!isset($_SESSION['admin_username'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $d_nid        = $_POST['d_nid'];
    $dmdc_id      = $_POST['dmdc_id'];
    $visiting_fee = $_POST['visiting_fee'];
    $password     = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $specialist   = $_POST['specialist'];
    $name         = $_POST['name'];
    $mobile_no    = $_POST['mobile_no'];
    $gender       = (int)$_POST['gender'];
    $blood        = $_POST['blood'];

    // New numeric fields
    $finger_print = isset($_POST['finger_print']) ? (int)$_POST['finger_print'] : null;
    $retina_print = isset($_POST['retina_print']) ? (int)$_POST['retina_print'] : null;

    $conn->begin_transaction();

    try {
        // Insert into person table with finger_print and retina_print
        $stmt1 = $conn->prepare("INSERT INTO person (nid, name, mobile_no, gender, blood, finger_print, retina_print) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt1->bind_param("sssissi", $d_nid, $name, $mobile_no, $gender, $blood, $finger_print, $retina_print);
        $stmt1->execute();

        // Insert into doctor table
        $stmt2 = $conn->prepare("INSERT INTO doctor (d_nid, dmdc_id, visiting_fee, password, specialist, name, mobile_no, gender, blood) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt2->bind_param("ssissssss", $d_nid, $dmdc_id, $visiting_fee, $password, $specialist, $name, $mobile_no, $gender, $blood);
        $stmt2->execute();

        $conn->commit();
        $message = "Doctor added successfully!";
        $message_type = "success";
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
        $message_type = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Doctor</title>
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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
            max-width: 1200px;
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
        
        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .btn-back {
            background: #f8f9fa;
            color: #6c757d;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-back:hover {
            background: #e9ecef;
            color: #495057;
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 12px;
            padding: 15px 40px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 1.1rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
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
        
        .input-icon .form-control, .input-icon .form-select {
            padding-left: 45px;
        }
        
        .avatar-container {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 50px;
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
        }
        
        .alert i {
            font-size: 1.2rem;
            margin-right: 10px;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        @media (max-width: 992px) {
            .card-body {
                padding: 30px;
            }
        }
        
        @media (max-width: 768px) {
            .card-body {
                padding: 20px;
            }
            
            .avatar {
                width: 100px;
                height: 100px;
                font-size: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h2 class="mb-0"><i class="fas fa-user-md me-2"></i>Add New Doctor</h2>
                    </div>
                    
                    <div class="card-body">
                        <a href="manage_doctors.php" class="btn btn-back mb-4">
                            <i class="fas fa-arrow-left"></i> Back to Doctors List
                        </a>

                        <?php if (isset($message)): ?>
                            <div class="alert alert-<?= $message_type ?>">
                                <i class="fas <?= $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i> 
                                <?= htmlspecialchars($message) ?>
                            </div>
                        <?php endif; ?>

                        <div class="avatar-container">
                            <div class="avatar">
                                <i class="fas fa-user-md"></i>
                            </div>
                        </div>

                        <form method="POST" action="">
                            <h4 class="section-title"><i class="fas fa-id-card me-2"></i>Professional Information</h4>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="d_nid" class="form-label required-field">NID</label>
                                    <div class="input-icon">
                                        <i class="fas fa-id-card"></i>
                                        <input type="text" name="d_nid" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="dmdc_id" class="form-label required-field">DMDC ID</label>
                                    <div class="input-icon">
                                        <i class="fas fa-id-badge"></i>
                                        <input type="text" name="dmdc_id" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="specialist" class="form-label required-field">Specialist Field</label>
                                    <div class="input-icon">
                                        <i class="fas fa-stethoscope"></i>
                                        <input type="text" name="specialist" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="visiting_fee" class="form-label required-field">Visiting Fee (৳)</label>
                                    <div class="input-icon">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <input type="number" name="visiting_fee" class="form-control" required min="0">
                                    </div>
                                </div>
                            </div>

                            <h4 class="section-title"><i class="fas fa-user me-2"></i>Personal Information</h4>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label required-field">Full Name</label>
                                    <div class="input-icon">
                                        <i class="fas fa-user"></i>
                                        <input type="text" name="name" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="mobile_no" class="form-label required-field">Mobile Number</label>
                                    <div class="input-icon">
                                        <i class="fas fa-phone"></i>
                                        <input type="text" name="mobile_no" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-4 mb-3">
                                    <label for="gender" class="form-label required-field">Gender</label>
                                    <div class="input-icon">
                                        <i class="fas fa-venus-mars"></i>
                                        <select name="gender" class="form-select" required>
                                            <option value="">Select Gender</option>
                                            <option value="1">Male</option>
                                            <option value="2">Female</option>
                                            <option value="3">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="blood" class="form-label required-field">Blood Group</label>
                                    <div class="input-icon">
                                        <i class="fas fa-tint"></i>
                                        <select name="blood" class="form-select" required>
                                            <option value="">Select Blood Group</option>
                                            <option value="A+">A+</option>
                                            <option value="A-">A-</option>
                                            <option value="B+">B+</option>
                                            <option value="B-">B-</option>
                                            <option value="O+">O+</option>
                                            <option value="O-">O-</option>
                                            <option value="AB+">AB+</option>
                                            <option value="AB-">AB-</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="password" class="form-label required-field">Password</label>
                                    <div class="input-icon">
                                        <i class="fas fa-lock"></i>
                                        <input type="password" name="password" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <h4 class="section-title"><i class="fas fa-fingerprint me-2"></i>Biometric Information</h4>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="finger_print" class="form-label required-field">Finger Print (Numeric)</label>
                                    <div class="input-icon">
                                        <i class="fas fa-fingerprint"></i>
                                        <input type="number" name="finger_print" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="retina_print" class="form-label required-field">Retina Print (Numeric)</label>
                                    <div class="input-icon">
                                        <i class="fas fa-eye"></i>
                                        <input type="number" name="retina_print" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-2"></i> Add Doctor
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add some interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-control, .form-select');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('focused');
                });
            });
        });
    </script>
</body>
</html>