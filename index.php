<!--

=========================================================
* Now UI Dashboard - v1.5.0
=========================================================

* Product Page: https://www.creative-tim.com/product/now-ui-dashboard
* Copyright 2019 Creative Tim (http://www.creative-tim.com)


-->
<?php 
  session_start();
  include_once('utils/redirect.php');
  if(!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    redirect('./Login/brandlogin.php');
  }
?>
<!DOCTYPE html>
<html lang="en">


<head>
  <meta charset="utf-8" />
  <link href="./assets/img/favicon.ico" rel="icon" type="image/x-icon" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <title>
    Kingsoft Portal
  </title>
  <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, shrink-to-fit=no'
    name='viewport' />
  <!--     Fonts and icons     -->
  <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@500&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700,200" rel="stylesheet" />
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css"
    integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
  <!-- CSS Files -->
  <link href="./assets/css/bootstrap.min.css" rel="stylesheet" />
  <link href="./assets/css/now-ui-dashboard.css?v=1.5.0" rel="stylesheet" />
  <!-- CSS Just for demo purpose, don't include it in your project -->
  <link href="./assets/demo/demo.css" rel="stylesheet" />
  <link href="./assets/kingsoft/style.css" rel="stylesheet" />
  <link href="./assets/kingsoft/kingsoft-jobcards.css" rel="stylesheet" />
  <link href="./assets/kingsoft/sidebar.css" rel="stylesheet" />
  <!-- <script src="http://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js" type="text/javascript"></script> -->
  <script src="https://cdn.jsdelivr.net/npm/@caneara/iodine@8.3.0/dist/iodine.min.umd.js"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <script src="./assets/js/app.js" type="text/javascript"></script>
  <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
  <script>
    const session = <?php echo json_encode([
      "id" => $_SESSION["user_id"],
      "username" => $_SESSION["username"],
      "email" => $_SESSION["email"],
      "phone" => $_SESSION["phone"],
      "task" => $_SESSION["task"],
      "location" => $_SESSION["location"],
      "role" => $_SESSION["role"],
    ]); ?>;
    Object.freeze(session)
    function scrollTo(elementId, delay = 400) {
      setTimeout(()=> {
        document.getElementById(elementId).scrollIntoView({behavior: 'smooth', block: 'start'})
      }, delay)
    }
  </script>
</head>

<body class="" style="max-height:98%;" x-data="{showSidebar: false, showJobcardForm: false, showUserSection: false}">
  <div class="wrapper ">
    <div class="sidebar-overlay" x-transition.opacity x-show="showSidebar" @click="showSidebar = false"></div>
    <div class="sidebar" :class="showSidebar && 'active'" data-color="orange">
      <!--
        Tip 1: You can change the color of the sidebar using: data-color="blue | green | orange | red | yellow"
    -->
      <div class="logo" style="font-family: 'Ubuntu'; font-weight: 500; position: relative;">
        <a href="#" class="simple-text logo-mini">
          K.
        </a>
        <a href="#" class="simple-text logo-normal">
          JOBCARDS
        </a>
        <button 
          class="icon-button" 
          @click="showSidebar = false"
        >
          <i class="now-ui-icons ui-1_simple-remove"></i>
        </button>
      </div>
      <div class="sidebar-wrapper" id="sidebar-wrapper" x-data="{page: new URLSearchParams(window.location.search).get('page')}">
        <ul class="nav">
          <li :class="!page && 'active'">
            <a href="./index.php">
              <i class="now-ui-icons design_app"></i>
              <p>Home</p>
            </a>
          </li>
          <template x-if="!page">
            <div class="page-options ml-4 mt-2 px-3" style="display: flex; flex-direction: column; gap: 4px;">
              <button class="btn" :class="showJobcardForm && 'show'" 
                @click="()=> {
                  if(!showJobcardForm) {
                    scrollTo('jobcard-form')
                  }
                  showJobcardForm = !showJobcardForm
                }"
                x-text="showJobcardForm ? 'Hide Jobcard form' : 'Show Jobcard form'"
              >
              </button>
              <button class="btn" :class="showUserSection && 'show'" 
                @click="()=> {
                  if(!showUserSection) {
                    scrollTo('user-section')
                  }
                  showUserSection = !showUserSection
                }"
                x-text="showUserSection ? 'Hide Clients & Users' : 'Show Clients & Users'"
              >
              </button>
            </div>
          </template>
          <li :class="page == 'profile' && 'active'">
            <a href="?page=profile">
              <i class="now-ui-icons users_single-02"></i>
              <p>Profile</p>
            </a>
          </li>
          <li>
            <a href="includes/logout.inc.php">
              <i class="now-ui-icons arrows-1_share-66" style="transform: rotate(-90deg);"></i>
              <p>Logout</p>
            </a>
          </li>
        </ul>
      </div>
    </div>
    <div class="main-panel" id="main-panel">
      <!-- Navbar -->
      <nav class="navbar navbar-expand-lg navbar-transparent  bg-primary  navbar-absolute">
        <div class="container-fluid">
          <div class="navbar-wrapper">
            <div class="navbar-toggle">
              <button type="button" class="navbar-toggler" @click ="showSidebar = !showSidebar">
                <span class="navbar-toggler-bar bar1"></span>
                <span class="navbar-toggler-bar bar2"></span>
                <span class="navbar-toggler-bar bar3"></span>
              </button>
            </div>
            <a class="navbar-brand" href="#">
              KINGSOFT JOBCARDS MANAGER
            </a>
          </div>

          <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navigation"
            aria-controls="navigation-index" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-bar navbar-kebab"></span>
            <span class="navbar-toggler-bar navbar-kebab"></span>
            <span class="navbar-toggler-bar navbar-kebab"></span>
          </button>
          <div class="collapse navbar-collapse justify-content-end" id="navigation">
            <ul class="navbar-nav">
              <li class="nav-item">
                <p>
                  <span class="text-uppercase" style="font-family: 'Montserrat';"><?php echo 'Hello '. $_SESSION['username'] ?></span>
                </p>
            </ul>
          </div>
        </div>
      </nav>
      <!-- End Navbar -->
      <div class="panel-header panel-header-lg">
      </div>
      <div class="content" id="content_id" style="margin-top:40px; font-family: 'Verdana';">
        <?php

          if(isset($_GET["page"])) {
            include_once('edit_profile.php');
          } else {
            include_once('add_jobcard.php');
            include_once('jobs_table.php');
            include_once('users_section.php');
          }
        ?>
        <div id="alert-slot" style="max-width: 300px; position: fixed; top: 2%; right: 5%; z-index: 999;">

        </div>
      </div>
      <footer class="footer">
        <div class=" container-fluid ">
          <nav>
            <ul>
              <li>
                <a href="#">
                  <!--                  Creative Tim-->
                </a>
              </li>
              <li>
                <a href="#">
                  <!--                  About Us-->
                </a>
              </li>
              <li>
                <a href="#">
                  <!--                  Blog-->
                </a>
              </li>
            </ul>
          </nav>
          <div class="copyright" id="copyright">
            &copy;
            <script>
              document.getElementById('copyright').appendChild(document.createTextNode(new Date().getFullYear()))
            </script> <a href="#" target="_blank"></a>
          </div>
        </div>
      </footer>
    </div>
  </div>
  <!--   Core JS Files   -->
  <script src="./assets/js/core/jquery.min.js"></script>
  <script src="./assets/js/core/popper.min.js"></script>
  <script src="./assets/js/core/bootstrap.min.js"></script>
</body>

</html>