<!DOCTYPE html>
<html>
<head>
  <title>Login | FSM</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>

input[type=text], input[type=email], input[type=password], select, textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid #ccc;
  border-radius: 4px;
  box-sizing: border-box;
  margin-top: 6px;
  margin-bottom: 16px;
  resize: vertical;
}

input[type=submit] {
  background-color: #04AA6D;
  color: white;
  padding: 12px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

input[type=submit]:hover {
  background-color: #45a049;
}

.container {
  border-radius: 5px;
  padding: 20px;
}

.col{
  background-color: #f2f2f2;
}

.d-flex{
  display: flex;
    justify-content: center;
}

.m-1{
          margin: 1%;
        }

        .text-center{
          text-align: center;
        }
</style>
</head>
<body>



<div class="container">

<?php
session_start();
if(isset($_SESSION['data']['is_login'])){
  header("location:index.php");
}

if(isset($_GET['error'])){
?>
  <div class="row m-1 text-center">
    <div class="col" style="
    padding: 1%;
    color: red;
">
      Invalid Information please enter valid information
    </div>
  </div>
  <?php } ?>

  <div class="row d-flex">
    <div class="col" style="padding: 1%;">
      <form action="./action_page.php" method="post">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Your email..">
    
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Your password..">
    
        <input type="submit" value="Login" name="login">
      </form>
    </div>
  </div>
</div>

</body>
</html>
