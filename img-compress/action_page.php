<?php
$email = 'admin@admin.com';
$pass = 'Dxdev@123';
if(!session_start()){
    session_start();
}
if (isset($_POST['login'])) {
    if ($email == $_POST['email'] && $pass == $_POST['password']) {
        $_SESSION['data']['email'] = $email;
        $_SESSION['data']['is_login'] = true;
        $_SESSION['data']['time'] = time();
        header('location:index.php');
    } else {
        header('location:login.php?error=true');
    }
}
    
?>