<?php
include_once('../dbconn.php');
session_start();

if (isset($_GET['hospital_id'])) {
    $hospital_id = $_GET['hospital_id'];

    // Prevent SQL Injection (use prepared statement if needed)
    $stmt = $conn->prepare("SELECT * FROM hospital WHERE hospital_id = ?");
    $stmt->bind_param("s", $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "Hospital not found.";
        exit();
    }

    $hospital = $result->fetch_assoc();
} else {
    header("Location: manage_hospitals.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hospital_name = trim($_POST['hospital_name']);
    $numberof_ward = intval($_POST['numberof_ward']);
    $numberof_cabin = intval($_POST['numberof_cabin']);
    $docreg = trim($_POST['docreg']);

    $update_query = "UPDATE hospital 
                     SET hospital_name = ?, numberof_ward = ?, numberof_cabin = ?, docreg = ? 
                     WHERE hospital_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("siiss", $hospital_name, $numberof_ward, $numberof_cabin, $docreg, $hospital_id);

    if ($stmt->execute()) {
        $success = "Hospital information updated successfully.";
        // Refresh data
        $hospital['hospital_name'] = $hospital_name;
        $hospital['numberof_ward'] = $numberof_ward;
        $hospital['numberof_cabin'] = $numberof_cabin;
        $hospital['docreg'] = $docreg;
    } else {
        $error = "Error updating hospital details: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Hospital</title>
    
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
            background: linear-gradient(135deg, #f5f7fb 0%, #e4e9f2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
        }
        
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 25px;
            text-align: center;
            border-bottom: none;
        }
        
        .card-body {
            padding: 30px;
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
            padding: 12px 30px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
            margin-bottom: 25px;
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
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
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
        
        .stat-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 14px;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .card {
                margin: 20px;
            }
            
            .card-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0"><i class="fas fa-hospital me-2"></i>Edit Hospital Information</h3>
                    </div>
                    
                    <div class="card-body">
                        <a href="manage_hospitals.php" class="btn btn-back mb-4">
                            <i class="fas fa-arrow-left"></i> Back to Hospital List
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

                        <div class="row mb-4">
                            <div class="col-md-3 col-6">
                                <div class="stat-card">
                                    <div class="stat-number"><?= htmlspecialchars($hospital['hospital_id']) ?></div>
                                    <div class="stat-label">Hospital ID</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="stat-card">
                                    <div class="stat-number"><?= htmlspecialchars($hospital['numberof_ward']) ?></div>
                                    <div class="stat-label">Wards</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="stat-card">
                                    <div class="stat-number"><?= htmlspecialchars($hospital['numberof_cabin']) ?></div>
                                    <div class="stat-label">Cabins</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="stat-card">
                                    <div class="stat-number"><?= htmlspecialchars($hospital['docreg']) ?></div>
                                    <div class="stat-label">Doctors</div>
                                </div>
                            </div>
                        </div>

                        <form method="POST">
                            <div class="mb-4">
                                <label for="hospital_name" class="form-label required-field">Hospital Name</label>
                                <div class="input-icon">
                                    <i class="fas fa-hospital"></i>
                                    <input type="text" class="form-control" id="hospital_name" name="hospital_name"
                                           value="<?= htmlspecialchars($hospital['hospital_name']) ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="numberof_ward" class="form-label required-field">Number of Wards</label>
                                    <div class="input-icon">
                                        <i class="fas fa-procedures"></i>
                                        <input type="number" class="form-control" id="numberof_ward" name="numberof_ward"
                                               value="<?= htmlspecialchars($hospital['numberof_ward']) ?>" required min="0">
                                    </div>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <label for="numberof_cabin" class="form-label required-field">Number of Cabins</label>
                                    <div class="input-icon">
                                        <i class="fas fa-bed"></i>
                                        <input type="number" class="form-control" id="numberof_cabin" name="numberof_cabin"
                                               value="<?= htmlspecialchars($hospital['numberof_cabin']) ?>" required min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="docreg" class="form-label required-field">Doctor Registration</label>
                                <div class="input-icon">
                                    <i class="fas fa-user-md"></i>
                                    <input type="text" class="form-control" id="docreg" name="docreg"
                                           value="<?= htmlspecialchars($hospital['docreg']) ?>" required>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Update Hospital Information
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
            const inputs = document.querySelectorAll('.form-control');
            
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