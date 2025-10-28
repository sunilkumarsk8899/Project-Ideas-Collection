<?php
// index.php - server-side form handling version of index.html (no AJAX required)
session_start();

// Include auth functions (does not auto-run when included)
require_once __DIR__ . '/auth.php';

$message = '';
$message_type = 'info'; // 'success' or 'error'

// If the form was submitted, handle via auth_handle_request(false)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = auth_handle_request(false);
    if ($result['success']) {
        // If this was a login, redirect to portal
        if (isset($_POST['action']) && $_POST['action'] === 'login') {
            header('Location: portal.php');
            exit;
        }
        // For registration, show a success message
        $message = $result['message'];
        $message_type = 'success';
    } else {
        $message = $result['message'];
        $message_type = 'error';
    }
}

// Helper to escape output
function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Portal - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .form-container { display: none; }
        .form-container.active { display: block; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md bg-white p-8 rounded-lg shadow-md">
        
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-blue-600">School Portal</h1>
            <p class="text-gray-500">Please log in or register to continue.</p>
        </div>

        <!-- Toggles -->
        <div class="flex mb-4 border-b">
            <button id="show-login" class="flex-1 py-2 font-semibold text-gray-700 border-b-2 border-blue-500">Login</button>
            <button id="show-register" class="flex-1 py-2 font-semibold text-gray-500 border-transparent">Register</button>
        </div>

        <!-- Message Box -->
        <?php if ($message): ?>
            <div id="message-box" class="p-3 rounded-lg mb-4 text-center text-sm <?= $message_type === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
                <?= h($message) ?>
            </div>
        <?php else: ?>
            <div id="message-box" class="p-3 rounded-lg mb-4 text-center text-sm" style="display: none;"></div>
        <?php endif; ?>

        <!-- Login Form -->
        <form id="login-form" class="form-container active space-y-4" method="post" action="">
            <input type="hidden" name="action" value="login">
            <div>
                <label for="login-email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="login-email" name="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required value="<?= h($_POST['email'] ?? '') ?>">
            </div>
            <div>
                <label for="login-password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="login-password" name="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
            </div>
            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">Login</button>
        </form>

        <!-- Register Form -->
        <form id="register-form" class="form-container space-y-4" method="post" action="">
            <input type="hidden" name="action" value="register">
            <div>
                <label for="register-email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="register-email" name="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required value="<?= h($_POST['email'] ?? '') ?>">
            </div>
            <div>
                <label for="register-password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="register-password" name="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Role</label>
                <select name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    <option value="student" <?= (($_POST['role'] ?? '') === 'student') ? 'selected' : '' ?>>I am a Student / Parent</option>
                    <option value="teacher" <?= (($_POST['role'] ?? '') === 'teacher') ? 'selected' : '' ?>>I am a Teacher</option>
                </select>
            </div>
            <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg">Register</button>
        </form>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const showLoginBtn = document.getElementById('show-login');
            const showRegisterBtn = document.getElementById('show-register');
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            const messageBox = document.getElementById('message-box');

            function showMessage(message, isError = false) {
                if (!messageBox) return;
                messageBox.textContent = message;
                messageBox.className = isError 
                    ? 'p-3 rounded-lg mb-4 text-center text-sm bg-red-100 text-red-700' 
                    : 'p-3 rounded-lg mb-4 text-center text-sm bg-green-100 text-green-700';
                messageBox.style.display = 'block';
            }

            showLoginBtn.addEventListener('click', () => {
                loginForm.classList.add('active');
                registerForm.classList.remove('active');
                showLoginBtn.classList.add('border-blue-500', 'text-gray-700');
                showLoginBtn.classList.remove('border-transparent', 'text-gray-500');
                showRegisterBtn.classList.add('border-transparent', 'text-gray-500');
                showRegisterBtn.classList.remove('border-blue-500', 'text-gray-700');
            });

            showRegisterBtn.addEventListener('click', () => {
                loginForm.classList.remove('active');
                registerForm.classList.add('active');
                showRegisterBtn.classList.add('border-blue-500', 'text-gray-700');
                showRegisterBtn.classList.remove('border-transparent', 'text-gray-500');
                showLoginBtn.classList.add('border-transparent', 'text-gray-500');
                showLoginBtn.classList.remove('border-blue-500', 'text-gray-700');
            });
        });
    </script>
</body>
</html>
