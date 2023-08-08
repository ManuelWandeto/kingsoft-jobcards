<?php
$page = "index";
$pagetitle = "Welcome";

include 'head.php';
?>


<body class="login-page sidebar-mini ">
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-transparent  bg-primary  navbar-absolute">
    <div class="container-fluid">
      <div class="navbar-wrapper"><a class="navbar-brand" href="#">
          <?php echo $pagetitle; ?>
        </a></div>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navigation"
        aria-controls="navigation-index" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-bar navbar-kebab"></span>
        <span class="navbar-toggler-bar navbar-kebab"></span>
        <span class="navbar-toggler-bar navbar-kebab"></span>
      </button>
      <div class="collapse navbar-collapse justify-content-end" id="navigation">
        <ul class="navbar-nav">
        </ul>
      </div>
    </div>
  </nav>
  <!-- End Navbar -->

  <div class="wrapper wrapper-full-page" style="background-color: #2c2c2c;">
    <div class="full-page login-page section-image" filter-color="black" style="background-color:#2c2c2c">
      <!--   you can change the color of the filter page using: data-color="blue | purple | green | orange | red | rose " -->
      <div class="content">
        <div class="container">
          <div class="col-md-6 ml-auto mr-auto">
            <div class="card card-invoice mt-5">
              <div class="card-header text-center" data-color-icon="warning">
                <div class="row">
                  <div class="col-12" style="padding:0px">
                    <div class="logo-container px-2"><img src="../assets/img/kingsoft.png" alt=""></div>
                    <br>
                  </div>
                </div>
              </div>

              <h3 style="text-align: center;"> Hotelex Smart Dashboard </h3>
              <div class="card-body">
                <div class="row">
                  <div class="col-12">
                  </div>
                </div>
              </div>
              <hr class="hr">
              <div class="card-footer" style="display: flex; justify-content: end;">
                <div><a class="btn btn-outline-primary" href="custom-login.php">Login</a></div>
              </div>

            </div>
          </div>
        </div>
      </div>
      <?php
      include 'footer.php';
      ?>