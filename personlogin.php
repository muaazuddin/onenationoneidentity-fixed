
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <!------ Include the above in your HEAD tag ---------->
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <!------ Include the above in your HEAD tag ---------->

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.0/css/bootstrap.min.css" integrity="sha384-SI27wrMjH3ZZ89r4o+fGIJtnzkAnFs3E4qz9DIYioCQ5l9Rd/7UAa8DHcaL8jkWt" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.3.2/jquery.rateyo.min.css">

    <title>Identity Based Healthcare Tutorial</title>
    <?php include('dbconn.php'); ?>
    <?php include('lib_db.php'); ?>
<?php
$message = "";
session_start();
require_once 'lib_db.php'; // use PDO wrapper

function generate_otp($length = 6) {
    $otp = '';
    for ($i = 0; $i < $length; $i++) $otp .= rand(0, 9);
    return $otp;
}
function create_mfa_token($user_type, $user_nid, $otp) {
    $hash = hash('sha256', $otp . 'SOME_STATIC_SALT');
    $expires = date('Y-m-d H:i:s', time() + 600);
    db_query("INSERT INTO mfa_tokens (user_type,user_nid,otp_hash,expires_at) VALUES (?,?,?,?)",
             [$user_type, $user_nid, $hash, $expires]);
}

if (isset($_POST['btn'])) {
    $nid = trim($_POST['nid']);
    $password = trim($_POST['password']);
    $radio = $_POST['radio'];

    if ($radio == 'Patient') {
        $rows = db_query("SELECT p_nid AS nid, password, email FROM patient WHERE p_nid=? AND password=?", [$nid, $password]);

        if ($rows) {
            $user = $rows[0];
            $user_id = $user['nid'];
            $email   = $user['email'];
            $user_type = 'patient';
        }
    } else {
        $rows = db_query("SELECT d_nid AS nid, password, email FROM doctor WHERE d_nid=? AND password=?", [$nid, $password]);

        if ($rows) {
            $user = $rows[0];
            $user_id = $user['nid'];
            $email   = $user['email'];
            $user_type = 'doctor';
        }
    }

    if (!empty($rows)) {
        //Password correct → Generate OTP
        $otp = generate_otp();
        create_mfa_token($user_type, $user_id, $otp);

        // Save info for MFA step
        $_SESSION['mfa_user_type'] = $user_type;
        $_SESSION['mfa_user_nid']  = $user_id;
        $_SESSION['mfa_role']      = $radio;

        $subject = "Your Login OTP Code";
        $body    = "Hello,\n\nYour OTP is: $otp\nThis will expire in 10 minutes.\n\nDo not share it.";
        $headers = "From: noreply@yourdomain.com";

        // Try sending email, but suppress warnings with @
        if (@mail($email, $subject, $body, $headers)) {
            echo "<p>OTP has been sent to your email ($email)</p>";
        } else {
            // Always fallback
            // echo "<p>Email not configured. Use this OTP for testing: <b>$otp</b></p>";
        }


        // Show OTP form
        echo '
          <div class="container mt-5">
            <div class="row justify-content-center">
              <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg border-0 rounded-3">
                  <div class="card-body p-4">
                    <h4 class="card-title text-center mb-4">OTP Verification</h4>
                    <p class="text-muted text-center">
                      Enter the 6-digit code — ' . ($otp != '' ? '<strong style="color: #28a745;">'.$otp.'</strong>' : '') . '
                    </p>
                    <form method="post" action="verify_mfa.php">
                      <div class="form-group mb-3">
                        <label for="otp" class="form-label">One-Time Password</label>
                        <input type="text" class="form-control form-control-lg text-center" 
                              id="otp" name="otp" maxlength="6" required 
                              placeholder="Enter OTP">
                      </div>
                      <button type="submit" class="btn btn-success btn-lg w-100">
                        Verify OTP
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>';
        exit;
    } else {
        $message = "Login failed";
    }
}
?>




    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <!-- Custom styles for this template -->
    <link href="css/blog-home.css" rel="stylesheet">
    <!-- <link rel="stylesheet" href="css/login.css"/> -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <style>
      .middlecolumn{
    /* border: 1px solid black; */
    /* background: black; */
    top: 55px;
    left: 0;
    right: 0;
    width: 100%;
    height: 100%;
    margin: auto;
    position: relative;
}
.middlecolumn .rightside{
    /* border: 1px solid black; */
    box-shadow: rgba(0, 0, 0, 0.4) 0px 30px 90px;
    border-radius: 5px;
    height: 470px;
    padding-top: 60px;
    padding-left: 30px;
    padding-right: 30px;
    /* padding: 20px; */
    top: 120px;
    right:120px;
    
    width: 35%;
    position:absolute;
    /* box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px; */
    outline: 2px solid #009B46;
}
.middlecolumn .leftside{
    top: 250px; ;
    left: 120px;
    position:absolute;
}


.logo_img img {
          height: 60px;
      }
      nav.navbar {
          background: #009B46 !important;
      }
      li.nav-item a {
          color: #000 !important;
          font-size: 25px;
          font-weight: 700;
      }

      li.nav-item:hover a {
          color: #fff !important;
          border-bottom: 2px solid #fff;
      }
      .btn-primary:hover {
                color: #fff !important;
                background-color: #009B46 !important;
                border-color: #009B46 !important;
            }
            input:focus{
              outline: 1px solid #009B46;
            }
    </style>

  </head>

  <body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
      <div class="container">
        <!-- <a class="navbar-brand" href="index.html">Identity Based Healthcare</a> -->
        <div class="logo_img">
          <a href="index.html"><img src="img/bg_logo1.png" alt="logo"></a>
        </div>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        

          </div>
      </div>
    </nav>

    <div class="middlecolumn">
        <div class="leftside">
            <img src="img/logo.png">
        </div>
        <div class="rightside">
           <center><h4 class="text-danger"><?php echo $message; ?></h4></center>
            <form  method="post">
                <div class="row mb-3">
                    <label for="inputEmail3" class="col-sm-2 col-form-label">NID</label>
                    <div class="col-sm-10">
                    <input type="number" name="nid" class="form-control" id="nid">
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="inputPassword3" class="col-sm-2 col-form-label">Password</label>
                    <div class="col-sm-10">
                    <input type="password" name="password" class="form-control" id="Password">
                    </div>
                </div>
                <fieldset class="row mb-3">
                    <legend class="col-form-label col-sm-2 pt-0">Login as</legend>
                    <div class="col-sm-10">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="radio" id="radio" value="Patient" checked>
                        <label class="form-check-label" for="gridRadios1">
                        Patient
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="radio" id="radio" value="Doctor">
                        <label class="form-check-label" for="gridRadios2">
                        Doctor
                        </label>
                    </div>
                    
                    </div>
                </fieldset>

                
                
                <button type="submit" name="btn" class="btn btn-primary">Sign in</button>
                <hr>
                
                </form>
                &nbsp;<a href="patientreg.php"><button type="button" name="btn" class="btn btn-primary">Reg as Patient</button></a> &nbsp; <a href="doctorreg.php"><button type="button" name="btn" class="btn btn-primary">Reg as Doctor</button></a>
        </div>

    </div>


	

  </body>

</html>
