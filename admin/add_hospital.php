<?php
include_once('../dbconn.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hospital_name     = $_POST['hospital_name'];
    $numberof_ward     = $_POST['numberof_ward'];
    $wardfee_perday    = $_POST['wardfee_perday'];
    $numberof_cabin    = $_POST['numberof_cabin'];
    $cabinfee_perday   = $_POST['cabinfee_perday'];
    $password          = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $docreg            = $_POST['docreg'];
    $docregtext        = $_POST['docregtext'];

    // Insert query
    $stmt = $conn->prepare("INSERT INTO hospital 
        (hospital_id, hospital_name, numberof_ward, wardfee_perday, numberof_cabin, cabinfee_perday, password, docreg, docregtext)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $hospital_id = uniqid('HOS-'); // Auto-generate ID

    $stmt->bind_param("ssiiiisss", $hospital_id, $hospital_name, $numberof_ward, $wardfee_perday, $numberof_cabin, $cabinfee_perday, $password, $docreg, $docregtext);

    if ($stmt->execute()) {
        $success = "Hospital added successfully!";
    } else {
        $error = "Error adding hospital: " . $conn->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Hospital</title>
    
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
        
        .form-control, .form-select, .form-check-input {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
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
        
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .radio-option:hover {
            border-color: var(--primary);
        }
        
        .radio-option.selected {
            border-color: var(--primary);
            background-color: rgba(67, 97, 238, 0.1);
        }
        
        .radio-option input[type="radio"] {
            display: none;
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
            
            .radio-group {
                flex-direction: column;
                gap: 10px;
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
                        <h2 class="mb-0"><i class="fas fa-hospital me-2"></i>Register New Hospital</h2>
                    </div>
                    
                    <div class="card-body">
                        <a href="manage_hospitals.php" class="btn btn-back mb-4">
                            <i class="fas fa-arrow-left"></i> Back to Hospitals List
                        </a>

                        <?php if (isset($success)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> <?= $success ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                            </div>
                        <?php endif; ?>

                        <div class="avatar-container">
                            <div class="avatar">
                                <i class="fas fa-hospital"></i>
                            </div>
                        </div>

                        <form method="POST" action="">
                            <h4 class="section-title"><i class="fas fa-info-circle me-2"></i>Basic Information</h4>
                            <div class="mb-4">
                                <label for="hospital_name" class="form-label required-field">Hospital Name</label>
                                <div class="input-icon">
                                    <i class="fas fa-hospital"></i>
                                    <input type="text" name="hospital_name" id="hospital_name" class="form-control" required>
                                </div>
                            </div>

                            <h4 class="section-title"><i class="fas fa-bed me-2"></i>Ward Information</h4>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="numberof_ward" class="form-label required-field">Number of Wards</label>
                                    <div class="input-icon">
                                        <i class="fas fa-procedures"></i>
                                        <input type="number" name="numberof_ward" id="numberof_ward" class="form-control" required min="0">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="wardfee_perday" class="form-label required-field">Ward Fee/Day (৳)</label>
                                    <div class="input-icon">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <input type="number" name="wardfee_perday" id="wardfee_perday" class="form-control" required min="0">
                                    </div>
                                </div>
                            </div>

                            <h4 class="section-title"><i class="fas fa-door-closed me-2"></i>Cabin Information</h4>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="numberof_cabin" class="form-label required-field">Number of Cabins</label>
                                    <div class="input-icon">
                                        <i class="fas fa-bed"></i>
                                        <input type="number" name="numberof_cabin" id="numberof_cabin" class="form-control" required min="0">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="cabinfee_perday" class="form-label required-field">Cabin Fee/Day (৳)</label>
                                    <div class="input-icon">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <input type="number" name="cabinfee_perday" id="cabinfee_perday" class="form-control" required min="0">
                                    </div>
                                </div>
                            </div>

                            <h4 class="section-title"><i class="fas fa-lock me-2"></i>Security Information</h4>
                            <div class="mb-4">
                                <label for="password" class="form-label required-field">Hospital Login Password</label>
                                <div class="input-icon">
                                    <i class="fas fa-key"></i>
                                    <input type="password" name="password" id="password" class="form-control" required>
                                </div>
                            </div>

                            <h4 class="section-title"><i class="fas fa-user-md me-2"></i>Doctor Registration</h4>
                            <div class="mb-4">
                                <label class="form-label required-field">Doctor Registration Available?</label>
                                <div class="radio-group">
                                    <label class="radio-option" id="yesOption">
                                        <input type="radio" name="docreg" value="Y" required>
                                        <i class="fas fa-check-circle"></i> Yes
                                    </label>
                                    <label class="radio-option" id="noOption">
                                        <input type="radio" name="docreg" value="N">
                                        <i class="fas fa-times-circle"></i> No
                                    </label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="docregtext" class="form-label">Doctor Registration Details (Vacancy Information)</label>
                                <div class="input-icon">
                                    <i class="fas fa-file-alt"></i>
                                    <textarea name="docregtext" id="docregtext" class="form-control" rows="3" placeholder="Describe doctor registration availability and vacancy details..."></textarea>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-2"></i> Register Hospital
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
            const inputs = document.querySelectorAll('.form-control, .form-select, textarea');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('focused');
                });
            });
            
            // Radio button styling
            const radioOptions = document.querySelectorAll('.radio-option');
            radioOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remove selected class from all options
                    radioOptions.forEach(opt => opt.classList.remove('selected'));
                    // Add selected class to clicked option
                    this.classList.add('selected');
                    // Check the radio button
                    this.querySelector('input[type="radio"]').checked = true;
                });
            });
            
            // Auto-select the first radio option
            document.getElementById('yesOption').click();
        });
    </script>
</body>
</html>