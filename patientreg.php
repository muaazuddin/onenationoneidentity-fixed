
<?php include('dbconn.php'); ?> 
<?php include('lib_db.php'); ?>
<?php
    $message=""; 
    if(isset($_POST['submit'])){
        $nid = $_POST['nid'];
        $finger_print = $_POST['finger_print'];
        $retina_print = $_POST['retina_print'];
        $query1 = mysqli_query($conn,"SELECT * FROM person where nid = '$nid' and finger_print = '$finger_print' and retina_print = '$retina_print' and isalive = 'yes'");
        $person_row = mysqli_fetch_array($query1);
        $query2 = mysqli_query($conn,"SELECT * FROM patient where p_nid = '$nid'");
        $patient_row = mysqli_fetch_array($query2);
        if($person_row!=NULL && $patient_row==NULL){
            header("location:preview.php?id=".$nid);
        }else{
            $message = "Worng Information!";
        }
    }
?>
<html>
<style>

.center {
text-align: center;
color: red;
}
    *{margin: 0; padding: 0;}
    body{background: #ecf1f4; font-family: sans-serif;}
    
    .form-wrap{ width: 420px; background: #009B46; padding: 40px 30px; box-sizing: border-box; position: fixed; left: 50%; top: 56%; transform: translate(-50%, -50%);}
    h1{text-align: center; color: #fff; font-weight: normal; margin-bottom: 20px;}
    
    input:focus {
    outline: none;
    background: #000;
}

input {
    width: 100%;
    background: none;
    border: 1px solid #fff;
    border-radius: 3px;
    padding: 17px 14px;
    box-sizing: border-box;
    margin-bottom: 25px;
    font-size: 15px;
    color: #fff;
}
    
    input[type="button"]{ background: #bac675; border: 0; cursor: pointer; color: #3e3d3d;}
    input[type="button"]:hover{ background: #a4b15c; transition: .6s;}
    
    ::placeholder{color: #fff;}

    input[type="submit"] {
        background: #bac675;
        border: 0;
        cursor: pointer;
        color: #3e3d3d;
    }

    input[type="submit"]:hover {
        background: #a4b15c;
        transition: .6s;
    }
    /* navigation-area-css-start */
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
          /* border-bottom: 2px solid #fff; */
          box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
      }

      .hero-section {
            background: #6bb972;
            height: 100vh;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .hero-text {
            text-align: center;
        }
        .hero-text h1 {
            font-size: 3.5rem;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .hero-text p {
            font-size: 1.5rem;
            margin-bottom: 30px;
        }
        .btn-custom {
            background-color: #009B46;
            color: #fff;
            font-size: 18px;
            padding: 12px 24px;
            border-radius: 50px;
            transition: 0.3s;
        }
        .btn-custom:hover {
            background-color: #000;
            color: #fff;
            transition: 1s all;
        }
        .navbar-custom {
            background-color: #009B46 !important;
            font-size: 20px;
        }
        .logo_img {
            width: 20%;
            margin: 0 auto;
        }
</style>

<body style="background-color: #0b522b;">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
      <div class="container">
        <!-- <a class="navbar-brand" href="#">Identity Based Health System</a> -->
        <div class="logo_img">
          <a href="index.html"><img src="img/bg_logo1.png" alt="logo"></a>
        </div>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <!-- <div class="collapse navbar-collapse" id="navbarResponsive">
          <ul class="navbar-nav ml-auto">

            <li class="nav-item">
              <a id="home_nav_hospital" class="nav-link" href="hospitallogin.php">Hospital</a>
            </li>
            <li class="nav-item">
              <a id="home_nav_person" class="nav-link" href="personlogin.php">Person</a>
            </li>
            
            
          </ul>
        </div> -->
      </div>
    </nav>
    <div class="form-wrap">
    
        <form action="" method="POST">
        
            <h1>Register</h1>
            <center><font color="red"><h4><?php echo $message; ?></h4></font></center>
            <br>
            <input type="number" placeholder="nid"  name="nid">
            <input type="number" placeholder="finger print"  name="finger_print">
            <input type="number" placeholder="retina print"  name="retina_print">
           <!-- <input type="text" placeholder=password  name="password"> -->


           <a href='l.php?'>
            <input type="submit" name="submit" placeholder=submit value="Next"/>
</a>
        </form>
    
    </div>



</body>



</html>
