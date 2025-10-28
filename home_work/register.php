<?php
// register.php
include 'db_connect.php';
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $role = $conn->real_escape_string($_POST['role']);
    
    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Simple check to prevent empty values
    if (!empty($username) && !empty($password) && !empty($full_name) && !empty($role)) {
        // For simplicity, this form doesn't link students to parents. 
        // You would need another field for 'parent_username' and find their ID.
        $sql = "INSERT INTO users (username, password, role, full_name) VALUES ('$username', '$hashed_password', '$role', '$full_name')";
        
        if ($conn->query($sql) === TRUE) {
            $message = "New account created successfully! You can now <a href='login.php'>login</a>.";
        } else {
            if ($conn->errno == 1062) { // Duplicate entry
                $message = "Error: This username is already taken.";
            } else {
                $message = "Error: " . $sql . "<br>" . $conn->error;
            }
        }
    } else {
        $message = "All fields are required.";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .register-container { background-color: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 300px; }
        .register-container h2 { text-align: center; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; }
        .form-group input, .form-group select { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; padding: 0.75rem; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        .btn:hover { background-color: #218838; }
        .message { text-align: center; margin-top: 1rem; font-size: 0.9rem; }
        .login-link { text-align: center; margin-top: 1rem; }
        .login-link a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Create Account</h2>
        <form action="register.php" method="post">
            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="role">I am a:</label>
                <select id="role" name="role" required>
                    <option value="">Select Role...</option>
                    <option value="teacher">Teacher</option>
                    <option value="student">Student</option>
                    <option value="parent">Parent</option>
                </select>
            </div>
            <button type="submit" class="btn">Register</button>
        </form>
        <div class="message"><?php echo $message; ?></div>
        <div class="login-link">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</body>
</html>
