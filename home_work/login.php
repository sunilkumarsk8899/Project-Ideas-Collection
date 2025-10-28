<?php
// login.php
session_start();
include 'db_connect.php';
$error_message = '';

// If already logged in, redirect to their dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'teacher') {
        header("Location: teacher_dashboard.php");
    } elseif ($_SESSION['role'] == 'student') {
        header("Location: student_dashboard.php");
    } elseif ($_SESSION['role'] == 'parent') {
        header("Location: parent_dashboard.php");
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT user_id, password, role, full_name, parent_id FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $row['password'])) {
            // Password is correct! Start the session
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $username;
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['role'] = $row['role'];

            // Store parent_id if it exists (for students)
            if ($row['role'] == 'student') {
                $_SESSION['parent_id'] = $row['parent_id'];
            }
            
            // Redirect based on role
            if ($row['role'] == 'teacher') {
                header("Location: teacher_dashboard.php");
            } elseif ($row['role'] == 'student') {
                header("Location: student_dashboard.php");
            } elseif ($row['role'] == 'parent') {
                header("Location: parent_dashboard.php");
            }
            exit;
        } else {
            $error_message = "Invalid username or password.";
        }
    } else {
        $error_message = "Invalid username or password.";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homework App Login</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-container { background-color: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 300px; }
        .login-container h2 { text-align: center; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; }
        .form-group input { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; padding: 0.75rem; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        .btn:hover { background-color: #0056b3; }
        .error { color: red; text-align: center; margin-bottom: 1rem; font-size: 0.9rem; }
        .register-link { text-align: center; margin-top: 1rem; }
        .register-link a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <form action="login.php" method="post">
            <?php if ($error_message): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
         <div class="register-link">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</body>
</html>
