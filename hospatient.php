
<?php include('dbconn.php'); ?>
<?php include('session.php'); ?>
<?php include('lib_db.php'); ?>
<?php
  if($user_type !="Hospital"){
    header("location:index.html");
  }
?>
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
    <!-- <link rel="stylesheet" href="css/myprofile.css"/> -->


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

  <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
      <div class="container">
        <!-- <a class="navbar-brand" href="hospitalhome.php">Identity Based Healthcare</a> -->
        <div class="logo_img">
          <a href="index.html"><img src="img/bg_logo1.png" alt="logo"></a>
        </div>

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
          <ul class="navbar-nav ml-auto">
            
            <li class="nav-item">
              <a class="nav-link" href="hospitaldoctor.php">Doctor</a>
            </li>
            
            <li class="nav-item">
              <a class="nav-link" href="hospatient.php">Patient</a>
            </li>
         
            <li class="nav-item">
              <a class="nav-link" href="index.html">logout</a>
            </li>
			
          </ul>
		 
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
                $query = mysqli_query($conn,"SELECT * FROM person inner join patient on person.nid = patient.p_nid");
                while( $patient_row = mysqli_fetch_array($query)){
            ?>
            
            <div class="card-body" style="border: 1px solid gray; position:relative; border-radius: 20px;">
                <img src="img/<?php echo $patient_row['image']; ?>" class="box">
                <div style="top: 20px;left: 75px;; position:absolute;"><a href="hospatientprofile.php?id=<?php echo $patient_row['nid']; ?>"><?php echo $patient_row['name']; ?></a></div>
                <div style="top: 40px;left: 75px;; position:absolute; font-size:smaller;"><?php echo "Blood: ".$patient_row['blood']; ?></div>
              
            </div>
            <br>
            <?php } ?>
          </div>

          
          

           

        

        </div>

        <!-- Sidebar Widgets Column -->
        <div class="col-md-4">

          <!-- Search Widget -->
          <div class="border card my-4">
            <h5 class="card-header">Search</h5>
            <div class="card-body">
              <form action="hossearchpatient.php" method="POST">
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
