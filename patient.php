
<?php include('dbconn.php'); ?>
<?php include('session.php'); ?>
<?php include('lib_db.php'); ?>
<link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<!------ Include the above in your HEAD tag ---------->

<!DOCTYPE html>
<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Identity Based Healthcare</title>
    <!-- <?php include('dbconn.php'); ?>
	  <?php include('session.php'); ?> -->

    <!-- Bootstrap core CSS -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <!-- Custom styles for this template -->
    <link href="css/blog-home.css" rel="stylesheet">
    <link rel="stylesheet" href="css/net.css"/>
    <link rel="stylesheet" href="css/myprofile.css"/>


<style>
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
</style>
  </head>

  <body>
  <?php 
$query = mysqli_query($conn,"SELECT * from person inner join gender on person.gender = gender.gender_id where person.nid ='$user_id'");
$my = mysqli_fetch_array($query);
?>

  <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
      <div class="container">
        <!-- <a class="navbar-brand" href="home.php">Identity Based Healthcare</a> -->
        <div class="logo_img">
          <a href="index.html"><img src="img/bg_logo1.png" alt="logo"></a>
        </div>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
          <ul class="navbar-nav ml-auto">
            <?php
              
              if($user_type=='Doctor'){

            ?>
            
            <li class="nav-item">
              <a class="nav-link" href="patient.php">Patient</a>
            </li>
            <?php } else {?>
            <li class="nav-item ">
              <a class="nav-link" href="doctor.php">Doctor</a>
            </li>
            <?php } ?>
            <li class="nav-item">
              <a class="nav-link" href="hospital.php">Hospital</a>
            </li>
            <?php if($user_type == "Patient"){?>
              <li class="nav-item">
              <a class="nav-link" href="myprescription.php">Prescription</a>
            </li>
            <?php } ?>
            
            <!-- <li class="nav-item">
              <a class="nav-link" href="#">Services</a>
            </li> -->
            <li class="nav-item">
              <a class="nav-link" href="message.php?id=0">Message</a>
            </li>
            <?php
              
              if($user_type=='Doctor'){

            ?>
            <li class="na1">
              <!-- $query = mysqli_query($conn) -->
              <a class="nav-link" href="doctorprofile.php?id=<?php echo $user_id; ?>">
                <div class="divm">
                  <div class="divname" style="font-size: 12px;color: #000;">
                    <?php echo $my['name']; ?>
                  </div>
                  <div class="divid" style="font-size: 10px; color: #000;">
                  <?php echo $my['nid']; ?>
                  </div>
                </div>
              </a>
            </li>
            <?php } else {?>
            <li class="na1">
              <!-- $query = mysqli_query($conn) -->
              <a class="nav-link" href="myprofile.php?id=<?php echo $user_id; ?>">
                <div class="divm">
                  <div class="divname" style="font-size: 12p; font-weight: 500 !important;color: #000 !important; ">
                    <?php echo $my['name']; ?>
                  </div>
                  <div class="divid" style="font-size: 7px; font-weight: 600;color: #000">
                  <?php echo $my['nid']; ?>
                  </div>
                </div>
              </a>
            </li>
            <?php } ?>
            <li class="na2">
              <a style="color: #000;font-weight:600;" class="nav-link" href="logout.php">logout</a>
            </li>
			
          </ul>
		  <div class="ml-auto my-2 my-lg-0">
<div class="section" id="b-section-navbar-search-form" name="Navbar: search form"><div class="widget BlogSearch" data-version="2" id="BlogSearch1">

</div></div>
<!-- </div> -->
        </div>
      </div>
    </nav>
	
	





    <!-- Page Content -->
    <div class="container">

      <div class="row">

        <!-- Blog Entries Column -->
        <div class="col-md-8">

          

          <!-- Blog Post -->
          <div class="card mb-4">
<?php
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Try with common date column names
$date_columns_to_try = ['date', 'prescription_date', 'created_at', 'timestamp'];
$sql = null;
$valid_date_column = null;

foreach ($date_columns_to_try as $date_column) {
    // Escape the column name safely using real_escape_string
    $safe_column = $conn->real_escape_string($date_column);
    $query = "SHOW COLUMNS FROM prescription LIKE '$safe_column'";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $valid_date_column = $safe_column;
        break;
    }
}

// Step 2: Build query with or without date column
if ($valid_date_column) {
    $sql = "SELECT 
               patient.p_nid as nid,
               person.name,
               person.image,
               person.blood,
               MAX(prescription.$valid_date_column) as last_prescription
            FROM patient 
            INNER JOIN person ON person.nid = patient.p_nid 
            INNER JOIN prescription ON prescription.p_nid = patient.p_nid 
            WHERE prescription.d_nid = ? 
            GROUP BY patient.p_nid, person.name, person.image, person.blood";
} else {
    $sql = "SELECT 
               patient.p_nid as nid,
               person.name,
               person.image,
               person.blood
            FROM patient 
            INNER JOIN person ON person.nid = patient.p_nid 
            INNER JOIN prescription ON prescription.p_nid = patient.p_nid 
            WHERE prescription.d_nid = ? 
            GROUP BY patient.p_nid, person.name, person.image, person.blood";
}


$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

if (!$stmt->bind_param("s", $user_id)) {
    die("Error binding parameters: " . $stmt->error);
}

if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($patient_row = $result->fetch_assoc()) {
?>
    <div class="card-body" style="border: 1px solid gray; position:relative; border-radius: 20px;"> 
        <img src="img/<?php echo htmlspecialchars($patient_row['image'] ?? ''); ?>" class="box">
        <div style="top: 20px;left: 75px; position:absolute;">
            <a href="docseepatient.php?id=<?php echo htmlspecialchars($patient_row['nid'] ?? ''); ?>&a=mo">
                <?php echo htmlspecialchars($patient_row['name'] ?? ''); ?>
            </a>
        </div>
        <div style="top: 40px;left: 75px; position:absolute; font-size:smaller;">
            <?php echo "Blood: " . htmlspecialchars($patient_row['blood'] ?? ''); ?>
        </div>
    </div>
    <br>
<?php 
    }
} else {
    echo "<p>No patients found.</p>";
}

$stmt->close();
?>
</div>

          
          

           

        

        </div> 

        <!-- Sidebar Widgets Column -->
        <div class="col-md-4">

          <!-- Search Widget -->
          <div class="border card my-4">
            <h5 class="card-header">Search</h5>
            <div class="card-body">
              <form action="searchpatient.php" method="POST">
              <div class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Search for...">
                <span class="input-group-btn">
                  <button class="btn btn-secondary" type="submit" name="go">Go!</button>
                </span>
              </div>
                </form>
            </div>
          </div>

          <!-- Categories Widget -->
          
          

          
        </div>
          
      </div>
      <!-- /.row -->

    </div>
    <!-- /.container -->



   

  </body>

</html>
