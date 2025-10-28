<?php
// auth.php
// Handles login and registration logic.

// Determine whether this file is being requested directly (API) or included
$is_direct_call = (__FILE__ === $_SERVER['SCRIPT_FILENAME']);

if ($is_direct_call) {
    // --- CORS / preflight handling for direct API calls ---
    // Allow common local development origins and enable credentialed requests.
    $allowed_origins = [
        'http://localhost:8000',
        'http://127.0.0.1:8000',
        'http://localhost:5500',
        'http://127.0.0.1:5500'
    ];
    if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins, true)) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
        // Allow cookies/sessions to be sent cross-origin
        header('Access-Control-Allow-Credentials: true');
    }
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

    // Respond to preflight requests and exit early to avoid unnecessary work
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

/**
 * Handle auth request.
 * @param bool $asApi If true, will prepare for JSON output when called directly. If false, returns result array for server-side use.
 * @return array Response array with keys: success (bool) and message (string)
 */
function auth_handle_request($asApi = true)
{
    // Ensure DB and session are available
    include_once __DIR__ . '/db_config.php'; // should define $conn and start session

    $response = ['success' => false, 'message' => 'Invalid request.'];
    $action = $_POST['action'] ?? '';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // --- User Registration ---
        if ($action == 'register') {
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $role = trim($_POST['role'] ?? '');

            if (empty($email) || empty($password) || empty($role)) {
                $response['message'] = 'All fields are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'Invalid email format.';
            } elseif ($role != 'teacher' && $role != 'student') {
                $response['message'] = 'Invalid role.';
            } elseif (strlen($password) < 6) {
                $response['message'] = 'Password must be at least 6 characters long.';
            } else {
                // Check if email already exists
                $sql = "SELECT id FROM users WHERE email = ?";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $stmt->store_result();

                    if ($stmt->num_rows > 0) {
                        $response['message'] = 'This email is already registered.';
                    } else {
                        // Create new user
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $sql_insert = "INSERT INTO users (email, password, role) VALUES (?, ?, ?)";

                        if ($stmt_insert = $conn->prepare($sql_insert)) {
                            $stmt_insert->bind_param("sss", $email, $hashed_password, $role);
                            if ($stmt_insert->execute()) {
                                $response['success'] = true;
                                $response['message'] = 'Registration successful! Please log in.';
                            } else {
                                $response['message'] = 'Something went wrong. Please try again.';
                            }
                            $stmt_insert->close();
                        }
                    }
                    $stmt->close();
                }
            }
        }

        // --- User Login ---
        elseif ($action == 'login') {
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if (empty($email) || empty($password)) {
                $response['message'] = 'Email and password are required.';
            } else {
                $sql = "SELECT id, email, password, role FROM users WHERE email = ?";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $stmt->store_result();

                    if ($stmt->num_rows == 1) {
                        $stmt->bind_result($id, $email_db, $hashed_password, $role);
                        if ($stmt->fetch()) {
                            if (password_verify($password, $hashed_password)) {
                                // Password is correct, start a new session
                                session_regenerate_id();
                                $_SESSION["loggedin"] = true;
                                $_SESSION["id"] = $id;
                                $_SESSION["email"] = $email_db;
                                $_SESSION["role"] = $role;

                                $response['success'] = true;
                                $response['message'] = 'Login successful! Redirecting...';
                            } else {
                                $response['message'] = 'Invalid email or password.';
                            }
                        }
                    } else {
                        $response['message'] = 'Invalid email or password.';
                    }
                    $stmt->close();
                }
            }
        }
    }

    if (isset($conn) && is_object($conn)) {
        $conn->close();
    }

    return $response;
}

// If this file is requested directly, handle as API and output JSON
if ($is_direct_call) {
    $resp = auth_handle_request(true);
    header('Content-Type: application/json');
    echo json_encode($resp);
    exit;
}

?>

