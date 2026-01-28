<?php
include_once('../dbconn.php');

if (isset($_GET['nid'])) {
    $nid = $_GET['nid'];

    $stmt = $conn->prepare("SELECT person.nid, person.name, doctor.specialist, 
                            person.mobile_no AS phone, person.gender, person.blood 
                            FROM doctor 
                            INNER JOIN person ON doctor.d_nid = person.nid
                            WHERE person.nid = ?");
    $stmt->bind_param("s", $nid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = $_POST['name'];
            $specialist = $_POST['specialist'];
            $phone = $_POST['phone'];
            $gender_text = $_POST['gender'];
                switch ($gender_text) {
                    case 'Male':
                        $gender = 1;
                        break;
                    case 'Female':
                        $gender = 2;
                        break;
                    case 'Other':
                        $gender = 3;
                        break;
                    default:
                        $gender = 0; 
                }

            $blood = $_POST['blood'];

            $updatePerson = $conn->prepare("UPDATE person SET name = ?, mobile_no = ?, gender = ?, blood = ? WHERE nid = ?");
            $updatePerson->bind_param("sssss", $name, $phone, $gender, $blood, $nid);
            $updatePerson->execute();

            $updateDoctor = $conn->prepare("UPDATE doctor SET specialist = ? WHERE d_nid = ?");
            $updateDoctor->bind_param("ss", $specialist, $nid);
            $updateDoctor->execute();

            header("Location: manage_doctors.php");
            exit();
        }
    } else {
        echo "<div class='alert alert-danger'>Doctor not found.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Doctor</title>
    
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
        
        .input-icon .form-control, .input-icon .form-select {
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
                        <h3 class="mb-0"><i class="fas fa-user-md me-2"></i>Edit Doctor Information</h3>
                    </div>
                    
                    <div class="card-body">
                        <a href="manage_doctors.php" class="btn btn-back mb-4">
                            <i class="fas fa-arrow-left"></i> Back to Doctor Management
                        </a>

                        <div class="avatar-container">
                            <div class="avatar">
                                <i class="fas fa-user-md"></i>
                            </div>
                        </div>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="name" class="form-label required-field">Full Name</label>
                                    <div class="input-icon">
                                        <i class="fas fa-user"></i>
                                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($row['name']) ?>" required>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <label for="specialist" class="form-label required-field">Specialist Field</label>
                                    <div class="input-icon">
                                        <i class="fas fa-stethoscope"></i>
                                        <input type="text" name="specialist" class="form-control" value="<?= htmlspecialchars($row['specialist']) ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="phone" class="form-label required-field">Mobile Number</label>
                                    <div class="input-icon">
                                        <i class="fas fa-phone"></i>
                                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($row['phone']) ?>" required>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <label for="gender" class="form-label required-field">Gender</label>
                                    <div class="input-icon">
                                        <i class="fas fa-venus-mars"></i>
                                        <select name="gender" class="form-select" required>
                                            <option value="Male" <?= $row['gender'] == 1 ? 'selected' : '' ?>>Male</option>
                                            <option value="Female" <?= $row['gender'] == 2 ? 'selected' : '' ?>>Female</option>
                                            <option value="Other" <?= $row['gender'] == 3 ? 'selected' : '' ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="blood" class="form-label required-field">Blood Group</label>
                                <div class="input-icon">
                                    <i class="fas fa-tint"></i>
                                    <select name="blood" class="form-select" required>
                                        <?php
                                        $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
                                        foreach ($bloodGroups as $group) {
                                            $selected = ($row['blood'] === $group) ? 'selected' : '';
                                            echo "<option value='$group' $selected>$group</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Update Doctor Information
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