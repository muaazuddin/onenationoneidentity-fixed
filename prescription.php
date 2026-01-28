
<?php
    include('dbconn.php');
    if (isset($_GET['preid'])) {
        $prescription_id = $_GET['preid'];
        $query = mysqli_query($conn, "SELECT * FROM prescription WHERE prescription_id = '$prescription_id'");

        if ($query && mysqli_num_rows($query) > 0) {
            $prescription = mysqli_fetch_array($query);
            $pid = $prescription['p_nid'];
            $did = $prescription['d_nid'];
            $medicines = $prescription['medicines'];

            // Fetch doctor's name based on $did
            $doctor_query = mysqli_query($conn, "SELECT name FROM person WHERE nid = '$did'");
            if ($doctor_query && mysqli_num_rows($doctor_query) > 0) {
                $doctorname = mysqli_fetch_array($doctor_query);
            } else {
                $doctorname = ['name' => 'Doctor not available'];
            }
        } else {
            echo "No prescription found for the provided ID.";
        }
    } else {
        echo "No prescription ID provided.";
    }
?>
<?php include('lib_db.php'); ?>

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

    <title>Identity Based Healthcare</title>
    <?php include('dbconn.php'); ?>
	  <?php include('session.php'); ?>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <!-- Custom styles for this template -->
    <link href="css/blog-home.css" rel="stylesheet">
    <link rel="stylesheet" href="css/myprofile.css"/>
    <link rel="stylesheet" href="new/css.css"/>
    <style>
      .container1{
          border: 1px solid gray;
          padding: 20px;
          top:70px;
          width: 75%;
          left:0px;
          right:0px;
          margin:auto;
          position:relative;
      }
      .card{
          border:0px;
          background:none;
      }
      .drug{
          left:0px;
          right:0px;
          margin:auto;
          position: relative;
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
              <a class="nav-link" href="message.php?id=0">message</a>
            </li>
            <?php
              
              if($user_type=='Doctor'){

            ?>
            <li class="na1">
              <!-- $query = mysqli_query($conn) -->
              <a class="nav-link" href="doctorprofile.php?id=<?php echo $user_id; ?>">
                <div class="divm">
                  <div class="divname" style="font-size: 15px; color: #000;">
                    <?php echo $my['name']; ?>
                  </div>
                  <div class="divid" style="font-size: 10px;color:#000">
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
                  <div class="divname special_class" style="font-size: 12p; font-weight: 500 !important;color: #000 !important; ">
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
	

<!-- add drug -->
<div class="modal fade" id="drugadd" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Add Drug</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="druginsert.php" method="POST">
      <div class="modal-body">
            <input type="hidden" name="prescription_id" class="form-control" value="<?php echo $prescription_id; ?>">
            <div class="form-group">
                <label class="col-form-label">Drug name:</label>
                <input type="text" name="drug_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="col-form-label">Power:</label>
                <input type="text" name="drug_power" class="form-control" required>
            </div>
            
            <div class="form-group">
                <select class="form-select" aria-label="Default select example" name="type" required>
                    <!-- <option selected>Drug Type</option> -->
                    <option value="Tablet">Tablet</option>
                    <option value="Capsule">capsule</option>
                    <option value="Syrup">Syrup</option>
                </select>
            </div>
            <div class="form-group">
                <label class="col-form-label">Description:</label>
                <input type="text" name="description" class="form-control" required>
            </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary" name="add">Add</button>
      </div>
    </form>
    </div>
  </div>
</div>
<!-- detect disease -->
<div class="modal fade" id="finddisease" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Delect Disease</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="detectdisease.php" method="POST">
      <div class="modal-body">
            <input type="hidden" name="prescription_id" class="form-control" value="<?php echo $prescription_id; ?>">
            <div class="form-group">
                <label class="col-form-label">Disease name:</label>
                <input type="text" name="disease" class="form-control" required>
            </div>
            
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary" name="delect">Add</button>
      </div>
    </form>
    </div>
  </div>
</div>


<!-- add test -->
<div class="modal fade" id="addtest" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Add Test</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="doctestinsert.php" method="POST">
      <div class="modal-body">
            <input type="hidden" name="prescription_id" class="form-control" value="<?php echo $prescription_id; ?>">
            
            <div class="form-group">
                <select class="form-select" aria-label="Default select example" name="test" required>
                    <!-- <option selected>Drug Type</option> -->
                    <?php
                       $query = mysqli_query($conn,"select * from test");
                       while($test = mysqli_fetch_array($query)){
                     ?>
                    <option value="<?php echo $test['test_id']; ?>"><?php echo $test['test_name']; ?></option>
                    <?php } ?>
                    
                </select>
            </div>
            
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary" name="testadd">Add</button>
      </div>
    </form>
    </div>
  </div>
</div>

<div class="container1">
    <h1>Prescription</h1>
    <br>
    <br>
    <div class="row">
      <div class="col-sm-6" align="center">
          <div class="card">
          
          <div class="input-group mb-3">
              <div class="input-group-prepend">
                  <span class="input-group-text" id="basic-addon3">Doctor Name</span>
              </div>
              
              <input type="text" class="form-control" value="<?php echo isset($doctorname['name']) ? $doctorname['name'] : 'Doctor not available'; ?>" disabled>



          </div>
              
          
      
          </div>
      </div>
      <?php 
// Ensure $prescription and $patientname are defined and valid before accessing them
if (isset($prescription) && is_array($prescription) && isset($prescription['childbirth_id'])) {
    if ($prescription['childbirth_id'] == "N/A") {
        ?>
        <div class="col-sm-6" align="center">
            <div class="card">
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon3">Patient Name</span>
                    </div>
                    <input type="text" class="form-control" value="<?php echo isset($patientname['name']) ? $patientname['name'] : ''; ?>" disabled>
                </div>        
            </div>
        </div>
    <?php 
    } else { ?>
        <div class="col-sm-6" align="center">
            <div class="card">
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon3">Guardian Name</span>
                    </div>
                    <input type="text" class="form-control" value="<?php echo isset($patientname['name']) ? $patientname['name'] : ''; ?>" disabled>
                </div>        
            </div>
        </div>
    <?php 
    }
}
?>

</div> <!-- Close any previously opened div if necessary -->
<br>

<?php 
// Second block - Ensure $prescription['childbirth_id'] is set and $childname is valid
if (isset($prescription) && is_array($prescription) && isset($prescription['childbirth_id']) && $prescription['childbirth_id'] != "N/A") {
    ?>
    <div class="row" style="left: 0px;right: 0px; margin:auto; position:relative; width: 100%;">
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text" id="basic-addon3">Patient Name</span>
            </div>
            <input type="text" class="form-control" value="<?php echo isset($childname['name']) ? $childname['name'] : ''; ?>" disabled>
        </div> 
    </div>
<?php 
}
?>

    <?php  ?>
    <br>
    <div class="row">
        
            <div class="drug" >
                <h3>Drug </h3>
            </div>
       
    </div>
    <br>
    <table class="table">
        <thead>
            <tr>
            <th scope="col">Drug Name</th>
            <th scope="col">Power</th>
            <th scope="col">Drug Type</th>
            <th scope="col">Description</th>
            <th scope="col">Delete</th>
            </tr>
        </thead>
        <tbody>
    <?php
        $query = mysqli_query($conn,"select * from drug where prescription_id = '$prescription_id'");
        while($pre = mysqli_fetch_array($query)){
     ?>
            <tr>
            <td ><?php echo $pre['drug_name']; ?></td>
            <td><?php echo $pre['power']; ?></td>
            <td><?php echo $pre['drug_type']; ?></td>
            <td><?php echo $pre['description']; ?></td>
            <td><a style="text-decoration:none;" href="deletedrug.php?drug_name=<?php echo $pre['drug_name']; ?>&preid=<?php echo $pre['prescription_id']; ?>&power=<?php echo $pre['power']; ?>&drug_type=<?php echo $pre['drug_type']; ?>"> <button type="button" class="btn btn-danger"> Delete </button></a></td>
            
            </tr>
        

    <?php } ?>
    </tbody>
    </table>

    
    
    <!-- <hr> -->
    <div class="row">
        <div class="col-sm-6">
            <!-- <div class="card" > -->
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#drugadd">
               add
            </button>
            <!-- </div> -->
        </div>
        
    </div>

    <div class="row">
        
            <div class="drug" >
                <h3>Detect Disease </h3>
            </div>
       
    </div>

    <table class="table">
        <thead>
            <tr>
            <th scope="col">Disease Name</th>
            <th scope="col">Delete</th>
            
            </tr>
        </thead>
        <tbody>
    <?php
        $query = mysqli_query($conn,"select * from detectdisease where prescription_id = '$prescription_id'");
        while($pre = mysqli_fetch_array($query)){
     ?>
            <tr>
            <td ><?php echo $pre['disease_name']; ?></td>
            <td><a style="text-decoration:none;" href="diseasedelete.php?diseasename=<?php echo $pre['disease_name']; ?>&preid=<?php echo $pre['prescription_id']; ?>"> <button type="button" class="btn btn-danger"> Delete </button></a></td>

            
            </tr>
        

    <?php } ?>
    </tbody>
    </table>

    <div class="row">
        <div class="col-sm-6">
            <!-- <div class="card" > -->
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#finddisease">
               detect disease
            </button>
            <!-- </div> -->
        </div>
        
    </div>


    <div class="row">
        
            <div class="drug" >
                <h3> Test </h3>
            </div>
       
    </div>

    <table class="table">
        <thead>
            <tr>
              <th scope="col">Test ID</th>
              <th scope="col">Test Name</th>
              <th scope="col">Date</th>
              <th scope="col">Delete</th>
            </tr>
        </thead>
        <tbody>
    <?php
        $query = mysqli_query($conn,"select * from doctorgivetest inner join test on doctorgivetest.test_id = test.test_id  where prescription_id = '$prescription_id'");
        while($pre = mysqli_fetch_array($query)){
     ?>
            <tr>
              <td ><?php echo $pre['test_id']; ?></td>
              <td><?php echo $pre['test_name']; ?></td>
              <td><?php echo $pre['giving']; ?></td>
              <td><a style="text-decoration:none;" href="deletetest.php?preid=<?php echo $prescription_id; ?>&test_id=<?php echo $pre['test_id']; ?>"> <button type="button" class="btn btn-danger"> Delete </button></a></td>

            </tr>
        

    <?php } ?>
    </tbody>
    </table>

    <div class="row">
        <div class="col-sm-6">
            <!-- <div class="card" > -->
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addtest">
               Add test
            </button>
            <!-- </div> -->
        </div>
        
    </div>
    <br>
      <form action="prescriptionupdate.php" method="POST">
          <input type="hidden" name="prescription_id" class="form-control" value="<?php echo $prescription_id; ?>">
            <div class="input-group mb-3">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="basic-addon3">Refer</span>
                </div>
                <input type="text" class="form-control" list="doctor_ref" name="doctor_ref">
                  <datalist id="doctor_ref">
                    <?php 
                      $query = mysqli_query($conn,"select * from doctor inner join person on doctor.d_nid = person.nid inner join dmdc on doctor.dmdc_id = dmdc.dmdc_id");
                      while($docref = mysqli_fetch_array($query)){
                    ?>
                    <option value="<?php echo $docref['dmdc_id']; ?>"><?php echo $docref['name']." (".$docref['specialist'].") "; ?> </option>
                    <?php } ?>
                  </datalist>
            </div>
            <div class="input-group mb-3">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="basic-addon3">Remarks</span>
                </div>
                <input type="text" class="form-control" name="remarks">
            </div>
            <button type="submit" class="btn btn-primary" name="submit">
               Save
            </button>

      </form>

</div>



<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>


</body>
</html>
