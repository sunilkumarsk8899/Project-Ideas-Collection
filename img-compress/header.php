<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title><?php echo $title; ?> | FSM</title>

    
    <link rel="stylesheet" href="style.css?ver=<?php echo time(); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if(isset($show) && $show == true){ ?>
    <script src="script.js?ver=<?php echo time(); ?>" defer></script>
    <?php } ?>

    

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip-utils/0.1.0/jszip-utils.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

  </head>
  <body>



  <?php
      session_start();
      /** check if user logged in or not if not than redirect to login page */
      // if (empty($_SESSION['data']['email']) || $_SESSION['data']['email'] == '' || $_SESSION['data']['is_login'] != true) {
      //   header("location:login.php");
      // }

      /** logout */
      // if(isset($_GET['action'])){
      //   if($_GET['action'] == 'logout'){
      //     session_unset();
      //     session_destroy();
      //     header("location:login.php");
      //   }
      // }

      /** logout after one day */
      // if(time() - $_SESSION['data']['time'] > 86400) { //subtract new timestamp from the old one
      //     unset($_SESSION['data']);
      //     session_destroy();
      //     header('location:login.php');
      //     exit;
      // } else {
      //     $_SESSION['timestamp'] = time(); //set new timestamp
      // }



      /** active menu */
    $uri = $_SERVER['REQUEST_URI'];
    function active_menu($uri,$menu_url){
        if($uri == $menu_url){
            return 'active';
        }else{
            return '';
        }
    }
  ?>



<div class="container">

<div class="container-fluid">
  <div class="topnav">
    <a class="<?php echo active_menu($uri,'/'); echo active_menu($uri,'/index.php'); ?>" href="/">Resize Images</a>
    <a class="<?php echo active_menu($uri,'/image_compress.php'); ?>" href="./image_compress.php">Compress Images</a>
      <a class="<?php echo active_menu($uri,'/image_background_remove.php'); ?>" href="/image_background_remove.php">Background Remove Images</a>
    <a class="" href="/?action=logout">Logout</a>
    <!-- <a href="/register.html">Register</a> -->
  </div>
</div>