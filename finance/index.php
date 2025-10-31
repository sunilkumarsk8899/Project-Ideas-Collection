<?php
// Start the session at the very beginning.
session_start();

// --- DATABASE CONFIGURATION ---
// !!! IMPORTANT: Fill in your MySQL database details here !!!
define('DB_HOST', '127.0.0.1'); // Or 'localhost'
define('DB_NAME', 'finance_tracker'); // The database name you created
define('DB_USER', 'root'); // Your MySQL username
define('DB_PASS', ''); // Your MySQL password
// ------------------------------

/**
 * Connects to the database using PDO.
 * Returns the PDO connection object or null on failure.
 */
function get_db_connection() {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // In a real app, you'd log this error, not just display it.
        error_log($e->getMessage());
        return null;
    }
}

/**
 * Redirects the user to a different page.
 */
function redirect($page) {
    header("Location: index.php?page=$page");
    exit;
}

/**
 * Checks if the user is logged in.
 * If not, redirects to the login page.
 */
function check_auth() {
    if (!isset($_SESSION['user_id'])) {
        redirect('login');
    }
}

// --- PAGE ROUTING & LOGIC ---

$page = $_GET['page'] ?? 'dashboard'; // Default page is dashboard
$pdo = get_db_connection();
$error_message = '';
$success_message = '';

if (!$pdo && $page !== 'db_error') {
    // If DB connection fails, show a specific error page
    $page = 'db_error';
}

switch ($page) {
    case 'login':
        // Handle login form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                $error_message = 'Please fill in all fields.';
            } else {
                $stmt = $pdo->prepare('SELECT id, password FROM users WHERE username = ?');
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Password is correct! Start the session.
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $username;
                    redirect('dashboard');
                } else {
                    $error_message = 'Invalid username or password.';
                }
            }
        }
        break;

    case 'register':
        // Handle registration form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';

            if (empty($username) || empty($password)) {
                $error_message = 'Please fill in all fields.';
            } elseif ($password !== $password_confirm) {
                $error_message = 'Passwords do not match.';
            } elseif (strlen($password) < 8) {
                $error_message = 'Password must be at least 8 characters long.';
            } else {
                // Check if username already exists
                $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $error_message = 'Username already taken.';
                } else {
                    // Create new user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
                    if ($stmt->execute([$username, $hashed_password])) {
                        $success_message = 'Registration successful! Please log in.';
                        // You could also log them in directly:
                        // $_SESSION['user_id'] = $pdo->lastInsertId();
                        // $_SESSION['username'] = $username;
                        // redirect('dashboard');
                    } else {
                        $error_message = 'An error occurred. Please try again.';
                    }
                }
            }
        }
        break;

    case 'logout':
        session_unset();
        session_destroy();
        redirect('login');
        break;

    case 'add_expense':
        check_auth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id = $_SESSION['user_id'];
            $month = $_POST['month'];
            $year = $_POST['year'];

            // Prepare data for insertion
            $data = [
                'user_id' => $user_id,
                'month' => (int)$month,
                'year' => (int)$year,
                'monthly_income' => (float)($_POST['monthly_income'] ?? 0), // <-- ADDED
                'petrol' => (float)($_POST['petrol'] ?? 0),
                'kitchen_food' => (float)($_POST['kitchen_food'] ?? 0),
                'out_food' => (float)($_POST['out_food'] ?? 0),
                'clothes' => (float)($_POST['clothes'] ?? 0),
                'wifi' => (float)($_POST['wifi'] ?? 0),
                'home' => (float)($_POST['home'] ?? 0),
                'milk' => (float)($_POST['milk'] ?? 0),
                'saving1' => (float)($_POST['saving1'] ?? 0),
                'saving2' => (float)($_POST['saving2'] ?? 0),
                'sip' => (float)($_POST['sip'] ?? 0),
                'insurance' => (float)($_POST['insurance'] ?? 0),
                'protein' => (float)($_POST['protein'] ?? 0),
                'creatine' => (float)($_POST['creatine'] ?? 0),
            ];

            // Use INSERT ... ON DUPLICATE KEY UPDATE to snapshot or update the month
            $sql = "INSERT INTO expenses (user_id, month, year, monthly_income, petrol, kitchen_food, out_food, clothes, wifi, home, milk, saving1, saving2, sip, insurance, protein, creatine)
                    VALUES (:user_id, :month, :year, :monthly_income, :petrol, :kitchen_food, :out_food, :clothes, :wifi, :home, :milk, :saving1, :saving2, :sip, :insurance, :protein, :creatine)
                    ON DUPLICATE KEY UPDATE
                    monthly_income = VALUES(monthly_income), petrol = VALUES(petrol), kitchen_food = VALUES(kitchen_food), out_food = VALUES(out_food), clothes = VALUES(clothes),
                    wifi = VALUES(wifi), home = VALUES(home), milk = VALUES(milk), saving1 = VALUES(saving1), saving2 = VALUES(saving2),
                    sip = VALUES(sip), insurance = VALUES(insurance), protein = VALUES(protein), creatine = VALUES(creatine)";

            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);
                $_SESSION['success_message'] = 'Expense record saved successfully for ' . date('F', mktime(0, 0, 0, $month, 10)) . ' ' . $year . '.';
            } catch (PDOException $e) {
                $_SESSION['error_message'] = 'Error saving record: ' . $e->getMessage();
            }
            redirect('dashboard');
        }
        break;
        
    case 'add_daily_expense':
        check_auth();
        $user_id = $_SESSION['user_id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $date = $_POST['date'];
            $amount = (float)($_POST['amount'] ?? 0);
            $category = $_POST['category'];
            $description = $_POST['description'] ?? '';

            if (empty($date) || $amount <= 0 || empty($category)) {
                $_SESSION['error_message'] = 'Please fill in date, amount, and category.';
                redirect('daily_tracker');
            }

            $month = date('m', strtotime($date));
            $year = date('Y', strtotime($date));

            // --- "Cannot Overspend" Check ---
            // 1. Get this month's budget
            $stmt = $pdo->prepare("SELECT monthly_income, total_savings FROM expenses WHERE user_id = ? AND month = ? AND year = ?");
            $stmt->execute([$user_id, $month, $year]);
            $budget = $stmt->fetch();

            if (!$budget) {
                $_SESSION['error_message'] = 'You must save a monthly snapshot for ' . date('F Y', strtotime($date)) . ' before you can add daily expenses.';
                redirect('daily_tracker');
            }

            // 2. Calculate available spending budget
            $available_to_spend = $budget['monthly_income'] - $budget['total_savings'];

            // 3. Get total already spent this month
            $stmt = $pdo->prepare("SELECT SUM(amount) as total_spent FROM daily_transactions WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
            $stmt->execute([$user_id, $month, $year]);
            $spent_data = $stmt->fetch();
            $total_spent = $spent_data['total_spent'] ?? 0;

            // 4. Check if this new expense will overspend
            $money_remaining = $available_to_spend - $total_spent;

            if ($amount > $money_remaining) {
                $_SESSION['error_message'] = 'This expense (₹' . number_format($amount, 2) . ') will exceed your remaining budget of ₹' . number_format($money_remaining, 2) . '.';
                redirect('daily_tracker');
            }
            // --- End Check ---

            // 5. If check passes, insert the transaction
            try {
                $stmt = $pdo->prepare("INSERT INTO daily_transactions (user_id, date, amount, category, description) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $date, $amount, $category, $description]);
                $_SESSION['success_message'] = 'Daily expense added successfully.';
            } catch (PDOException $e) {
                $_SESSION['error_message'] = 'Error adding expense: ' . $e->getMessage();
            }
        }
        redirect('daily_tracker');
        break;

    case 'delete_daily_expense':
        check_auth();
        $expense_id = $_GET['id'] ?? 0;
        $user_id = $_SESSION['user_id'];
        
        if ($expense_id > 0) {
            try {
                // Verify the expense belongs to the logged-in user before deleting
                $stmt = $pdo->prepare("DELETE FROM daily_transactions WHERE id = ? AND user_id = ?");
                $stmt->execute([$expense_id, $user_id]);
                
                if ($stmt->rowCount() > 0) {
                     $_SESSION['success_message'] = 'Daily expense deleted successfully.';
                } else {
                     $_SESSION['error_message'] = 'Could not delete record.';
                }
            } catch (PDOException $e) {
                 $_SESSION['error_message'] = 'Error deleting record: ' . $e->getMessage();
            }
        } else {
            $_SESSION['error_message'] = 'Invalid record ID.';
        }
        redirect('daily_tracker');
        break;

    case 'delete_expense':
        check_auth();
        $expense_id = $_GET['id'] ?? 0;
        $user_id = $_SESSION['user_id'];
        
        if ($expense_id > 0) {
            try {
                // Verify the expense belongs to the logged-in user before deleting
                $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
                $stmt->execute([$expense_id, $user_id]);
                
                if ($stmt->rowCount() > 0) {
                     $_SESSION['success_message'] = 'Expense record deleted successfully.';
                } else {
                     $_SESSION['error_message'] = 'Could not delete record. It may not exist or not belong to you.';
                }
            } catch (PDOException $e) {
                 $_SESSION['error_message'] = 'Error deleting record: ' . $e->getMessage();
            }
        } else {
            $_SESSION['error_message'] = 'Invalid record ID.';
        }
        redirect('dashboard');
        break;

    case 'dashboard':
        check_auth();
        // Fetch expense records for the user
        $user_id = $_SESSION['user_id'];
        
        // --- NEW: Filter Logic ---
        $filter = $_GET['filter'] ?? 'this_year'; // Default to 'this_year'
        $sql_filter = '';
        $current_year = date('Y');
        
        if ($filter === 'this_year') {
            $sql_filter = "AND year = " . intval($current_year);
        } elseif ($filter === 'last_12') {
            // Get the date 11 months ago (to make 12 months total including this one)
            $start_date_sql = date('Y-m-01', strtotime('-11 months'));
            $end_date_sql = date('Y-m-t'); // 't' gets the last day of the current month
            $sql_filter = "AND STR_TO_DATE(CONCAT(year, '-', month, '-01'), '%Y-%m-%d') BETWEEN '$start_date_sql' AND '$end_date_sql'";
        }
        // No 'else' needed, as 'all' means no additional filter ($sql_filter = '')
        
        $stmt = $pdo->prepare("SELECT * FROM expenses WHERE user_id = ? $sql_filter ORDER BY year DESC, month DESC");
        $stmt->execute([$user_id]);
        $expense_records = $stmt->fetchAll();
        
        // Check for flash messages from session
        if(isset($_SESSION['success_message'])) {
            $success_message = $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        }
        if(isset($_SESSION['error_message'])) {
            $error_message = $_SESSION['error_message'];
            unset($_SESSION['error_message']);
        }
        break;

    case 'daily_tracker':
        check_auth();
        $user_id = $_SESSION['user_id'];
        $current_month = date('m');
        $current_year = date('Y');
        
        // --- NEW: Filter Logic for Chart/List ---
        $filter_type = $_GET['filter_type'] ?? 'this_month';
        $start_date = $_GET['start_date'] ?? '';
        $end_date = $_GET['end_date'] ?? '';
        $filter_title = '';
        
        if ($filter_type === 'last_30_days') {
            $start_date_sql = date('Y-m-d', strtotime('-29 days'));
            $end_date_sql = date('Y-m-d');
            $filter_title = 'Last 30 Days';
        } elseif ($filter_type === 'custom' && !empty($start_date) && !empty($end_date)) {
            $start_date_sql = $start_date;
            $end_date_sql = $end_date;
            $filter_title = 'From ' . htmlspecialchars($start_date) . ' to ' . htmlspecialchars($end_date);
        } else {
            // Default to 'this_month'
            $filter_type = 'this_month';
            $start_date_sql = date('Y-m-01');
            $end_date_sql = date('Y-m-t');
            $filter_title = 'For ' . date('F Y');
        }
        
        $chart_start_date = $start_date_sql;
        $chart_end_date = $end_date_sql;
        $sql_date_filter = "AND date BETWEEN '$start_date_sql' AND '$end_date_sql'";
        
        // --- End Filter Logic ---

        // 1. Get this month's budget snapshot (for the Summary card - ALWAYS current month)
        $stmt = $pdo->prepare("SELECT * FROM expenses WHERE user_id = ? AND month = ? AND year = ?");
        $stmt->execute([$user_id, $current_month, $current_year]);
        $monthly_budget = $stmt->fetch();

        // 2. Get all transactions for this month (for the Summary card - ALWAYS current month)
        $stmt = $pdo->prepare("SELECT * FROM daily_transactions WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ? ORDER BY date DESC, id DESC");
        $stmt->execute([$user_id, $current_month, $current_year]);
        $current_month_transactions = $stmt->fetchAll(); // <-- Renamed variable

        // 2b. Get FILTERED transactions (for the Chart and List)
        $stmt = $pdo->prepare("SELECT * FROM daily_transactions WHERE user_id = ? $sql_date_filter ORDER BY date DESC, id DESC");
        $stmt->execute([$user_id]);
        $filtered_transactions = $stmt->fetchAll(); // <-- New variable for filtered data

        // 3. Calculate summary data (using ONLY current month data)
        $summary = [
            'income' => $monthly_budget['monthly_income'] ?? 0,
            'budgeted_savings' => $monthly_budget['total_savings'] ?? 0,
            'available_to_spend' => 0,
            'total_spent' => 0,
            'money_remaining' => 0,
            'percent_spent' => 0,
        ];

        if ($monthly_budget) {
            $summary['available_to_spend'] = $summary['income'] - $summary['budgeted_savings'];
            $summary['total_spent'] = array_sum(array_column($current_month_transactions, 'amount')); // <-- Use current month data
            $summary['money_remaining'] = $summary['available_to_spend'] - $summary['total_spent'];
            if ($summary['available_to_spend'] > 0) {
                $summary['percent_spent'] = ($summary['total_spent'] / $summary['available_to_spend']) * 100;
            }
        }
        
        // Check for flash messages
        if(isset($_SESSION['success_message'])) {
            $success_message = $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        }
        if(isset($_SESSION['error_message'])) {
            $error_message = $_SESSION['error_message'];
            unset($_SESSION['error_message']);
        }
        break;

    case 'db_error':
        // This case is handled by the HTML output function
        break;

    default:
        // Handle 404 Not Found
        http_response_code(404);
        $page = '404';
        break;
}

/**
 * Renders the HTML for the page header.
 */
function render_header($title) {
    $is_logged_in = isset($_SESSION['user_id']);
    $username = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - Finance Tracker</title>
    <!-- Load Tailwind CSS from CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Load Chart.js from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Simple transition for message boxes */
        .fade-out {
            opacity: 1;
            transition: opacity 0.5s ease-out;
        }
        .fade-out.hidden {
            opacity: 0;
        }
        /* Custom styles for expense table */
        .expense-table th, .expense-table td {
            @apply px-3 py-2 text-sm;
        }
        .expense-table th {
            @apply font-semibold text-left bg-gray-100;
        }
        .expense-table td {
            @apply border-t border-gray-200;
        }
        .expense-table .total-col {
            @apply font-bold text-blue-600;
        }
        .expense-table .savings-col {
            @apply font-bold text-green-600;
        }
    </style>
</head>
<body class="h-full font-sans antialiased text-gray-800">
    <div class="min-h-full">
        <nav class="bg-gray-800">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <!-- You can replace this with an SVG logo -->
                            <span class="text-2xl font-bold text-white">📈</span>
                        </div>
                        <div class="hidden md:block">
                            <div class="ml-10 flex items-baseline space-x-4">
                                <?php if ($is_logged_in): ?>
                                    <a href="index.php?page=dashboard" class="<?php echo ($page === 'dashboard' ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'); ?> rounded-md px-3 py-2 text-sm font-medium">Monthly Snapshot</a>
                                    <a href="index.php?page=daily_tracker" class="<?php echo ($page === 'daily_tracker' ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'); ?> rounded-md px-3 py-2 text-sm font-medium">Daily Tracker</a>
                                <?php else: ?>
                                    <a href="index.php?page=login" class="text-gray-300 hover:bg-gray-700 hover:text-white rounded-md px-3 py-2 text-sm font-medium">Login</a>
                                    <a href="index.php?page=register" class="text-gray-300 hover:bg-gray-700 hover:text-white rounded-md px-3 py-2 text-sm font-medium">Register</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($is_logged_in): ?>
                    <div class="hidden md:block">
                        <div class="ml-4 flex items-center md:ml-6">
                            <span class="text-gray-400 mr-3">Welcome, <?php echo htmlspecialchars($username); ?>!</span>
                            <a href="index.php?page=logout" class="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700">Logout</a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <header class="bg-white shadow-sm">
            <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                <h1 class="text-xl font-semibold tracking-tight text-gray-900"><?php echo htmlspecialchars($title); ?></h1>
            </div>
        </header>

        <main>
            <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
                <!-- Global error/success message box -->
                <?php if (!empty($error_message)): ?>
                    <div id="message-box" class="fade-out mb-4 rounded-md bg-red-100 p-4 text-sm text-red-700">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <div id="message-box" class="fade-out mb-4 rounded-md bg-green-100 p-4 text-sm text-green-700">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
<?php
}

/**
 * Renders the HTML for the page footer.
 */
function render_footer() {
?>
            </div>
        </main>
    </div>
    <script>
        // Auto-hide message boxes after 5 seconds
        const messageBox = document.getElementById('message-box');
        if (messageBox) {
            setTimeout(() => {
                messageBox.classList.add('hidden');
            }, 5000);
        }
    </script>
</body>
</html>
<?php
}

/**
 * Renders the content for a specific page.
 */
function render_page_content($page, $pdo, $data = []) {
    extract($data); // Extract variables for use in the page
    
    switch ($page) {
        case 'login':
        case 'register':
            $is_login = $page === 'login';
?>
            <div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8" style="margin-top: -50px;">
                <div class="sm:mx-auto sm:w-full sm:max-w-sm">
                    <h2 class="mt-10 text-center text-2xl font-bold leading-9 tracking-tight text-gray-900">
                        <?php echo $is_login ? 'Sign in to your account' : 'Create a new account'; ?>
                    </h2>
                </div>

                <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
                    <form class="space-y-6" action="index.php?page=<?php echo $page; ?>" method="POST">
                        <div>
                            <label for="username" class="block text-sm font-medium leading-6 text-gray-900">Username</label>
                            <div class="mt-2">
                                <input id="username" name="username" type="text" autocomplete="username" required class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 px-2">
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium leading-6 text-gray-900">Password</label>
                            <div class="mt-2">
                                <input id="password" name="password" type="password" autocomplete="<?php echo $is_login ? 'current-password' : 'new-password'; ?>" required class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 px-2">
                            </div>
                        </div>
                        
                        <?php if (!$is_login): ?>
                        <div>
                            <label for="password_confirm" class="block text-sm font-medium leading-6 text-gray-900">Confirm Password</label>
                            <div class="mt-2">
                                <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 px-2">
                            </div>
                        </div>
                        <?php endif; ?>

                        <div>
                            <button type="submit" class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                <?php echo $is_login ? 'Sign in' : 'Register'; ?>
                            </button>
                        </div>
                    </form>

                    <p class="mt-10 text-center text-sm text-gray-500">
                        <?php if ($is_login): ?>
                            Not a member?
                            <a href="index.php?page=register" class="font-semibold leading-6 text-indigo-600 hover:text-indigo-500">Register here</a>
                        <?php else: ?>
                            Already have an account?
                            <a href="index.php?page=login" class="font-semibold leading-6 text-indigo-600 hover:text-indigo-500">Sign in here</a>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
<?php
            break;

        case 'dashboard':
            $categories = [
                'Expenses' => ['petrol', 'kitchen_food', 'out_food', 'clothes', 'wifi', 'home', 'milk', 'insurance'],
                'Gym' => ['protein', 'creatine'],
                'Savings' => ['saving1', 'saving2', 'sip'],
            ];
            $current_month = date('m');
            $current_year = date('Y');
?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Add Expense Form -->
                <div class="lg:col-span-1">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-lg font-semibold mb-4 border-b pb-2">Save Monthly Snapshot</h3>
                        <form action="index.php?page=add_expense" method="POST" class="space-y-4">
                            <!-- Month/Year Selector -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="month" class="block text-sm font-medium text-gray-700">Month</label>
                                    <select id="month" name="month" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?php echo $m; ?>" <?php echo ($m == $current_month) ? 'selected' : ''; ?>>
                                                <?php echo date('F', mktime(0, 0, 0, $m, 10)); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="year" class="block text-sm font-medium text-gray-700">Year</label>
                                    <input type="number" name="year" id="year" value="<?php echo $current_year; ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-2 py-1.5" min="2000" max="2100">
                                </div>
                            </div>

                            <!-- ADDED: Monthly Income -->
                            <div>
                                <label for="monthly_income" class="block text-sm font-medium text-gray-700">Monthly Income (Salary)</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-gray-500 sm:text-sm">₹</span>
                                    </div>
                                    <input type="number" step="0.01" min="0" name="monthly_income" id="monthly_income" class="block w-full rounded-md border-gray-300 pl-7 pr-2 py-1.5 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="0.00">
                                </div>
                            </div>
                            
                            <!-- Category Sections -->
                            <?php foreach ($categories as $group_name => $items): ?>
                            <fieldset class="border-t border-gray-200 pt-4">
                                <legend class="text-md font-semibold text-gray-900 mb-2"><?php echo $group_name; ?></legend>
                                <div class="space-y-3">
                                    <?php foreach ($items as $item): ?>
                                    <div>
                                        <label for="<?php echo $item; ?>" class="block text-sm font-medium text-gray-700 capitalize"><?php echo str_replace('_', ' ', $item); ?></label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                <span class="text-gray-500 sm:text-sm">₹</span>
                                            </div>
                                            <input type="number" step="0.01" min="0" name="<?php echo $item; ?>" id="<?php echo $item; ?>" class="block w-full rounded-md border-gray-300 pl-7 pr-2 py-1.5 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="0.00">
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>
                            <?php endforeach; ?>

                            <div>
                                <button type="submit" id="snapshot-button" class="w-full flex justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition-colors duration-200">
                                    Save Snapshot
                                </button>
                                <p class="text-xs text-gray-500 mt-2 text-center">Note: Saving for a month that already exists will overwrite it.</p>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Expense History & Graph -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- ADDED: Monthly Graph -->
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex justify-between items-center mb-4 border-b pb-2">
                            <h3 class="text-lg font-semibold">Monthly Trends</h3>
                            <!-- NEW: Filter Buttons -->
                            <div class="flex space-x-2">
                                <?php $filter = $_GET['filter'] ?? 'this_year'; // Get filter for active state ?>
                                <a href="index.php?page=dashboard&filter=this_year" class="<?php echo ($filter === 'this_year' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'); ?> rounded-md px-3 py-1 text-xs font-medium">This Year</a>
                                <a href="index.php?page=dashboard&filter=last_12" class="<?php echo ($filter === 'last_12' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'); ?> rounded-md px-3 py-1 text-xs font-medium">Last 12 Months</a>
                                <a href="index.php?page=dashboard&filter=all" class="<?php echo ($filter === 'all' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'); ?> rounded-md px-3 py-1 text-xs font-medium">All Time</a>
                            </div>
                        </div>
                        
                        <?php if (empty($expense_records)): ?>
                            <p class="text-gray-500">Save your first snapshot to see a graph of your trends.</p>
                        <?php else: ?>
                            <div class="relative" style="height: 300px;">
                                <canvas id="monthlyChart"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Modified: Expense History Table -->
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-lg font-semibold mb-4 border-b pb-2">My Snapshot History</h3>
                        <?php if (empty($expense_records)): ?>
                            <p class="text-gray-500">You have not saved any expense snapshots yet. Use the form to add your first one.</p>
                        <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full expense-table">
                                <thead class="border-b border-gray-300">
                                    <tr>
                                        <th>Period</th>
                                        <th>Income</th>
                                        <th>Total Expenses</th>
                                        <th>Total Savings</th>
                                        <th>Net</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expense_records as $record): ?>
                                        <?php 
                                            $month_name = date('F', mktime(0, 0, 0, $record['month'], 10)); 
                                            $net = $record['monthly_income'] - $record['total_expenses'] - $record['total_savings'];
                                            $net_class = $net >= 0 ? 'text-green-600' : 'text-red-600';
                                        ?>
                                        <tr>
                                            <td class="font-medium"><?php echo htmlspecialchars($month_name . ' ' . $record['year']); ?></td>
                                            <td>₹<?php echo number_format($record['monthly_income'], 2); ?></td>
                                            <td class="total-col">₹<?php echo number_format($record['total_expenses'], 2); ?></td>
                                            <td class="savings-col">₹<?php echo number_format($record['total_savings'], 2); ?></td>
                                            <td class="font-bold <?php echo $net_class; ?>">₹<?php echo number_format($net, 2); ?></td>
                                            <td>
                                                <a href="index.php?page=delete_expense&id=<?php echo $record['id']; ?>" 
                                                   class="text-red-600 hover:text-red-800 text-xs"
                                                   onclick="return confirm('Are you sure you want to delete the record for <?php echo $month_name . ' ' . $record['year']; ?>? This cannot be undone.');">
                                                   Delete
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ADDED: JavaScript for Chart -->
            <?php if (!empty($expense_records)): ?>
            <script>
            document.addEventListener('DOMContentLoaded', () => {
                // Prepare data for the chart
                // PHP records are newest first, chart needs oldest first, so reverse
                const records = <?php echo json_encode(array_reverse($expense_records)); ?>;
                
                const labels = records.map(r => {
                    // Create a label like "Jan 2024"
                    const date = new Date(r.year, r.month - 1); // JS months are 0-indexed
                    return date.toLocaleString('default', { month: 'short', year: 'numeric' });
                });
                const incomeData = records.map(r => r.monthly_income);
                const expenseData = records.map(r => r.total_expenses);
                const savingsData = records.map(r => r.total_savings);

                const ctx = document.getElementById('monthlyChart').getContext('2d');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'bar', // You can change this to 'line' for a different view
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Income',
                                    data: incomeData,
                                    backgroundColor: 'rgba(54, 162, 235, 0.6)', // Blue
                                    borderColor: 'rgba(54, 162, 235, 1)',
                                    borderWidth: 1
                                },
                                {
                                    label: 'Expenses',
                                    data: expenseData,
                                    backgroundColor: 'rgba(255, 99, 132, 0.6)', // Red
                                    borderColor: 'rgba(255, 99, 132, 1)',
                                    borderWidth: 1
                                },
                                {
                                    label: 'Savings',
                                    data: savingsData,
                                    backgroundColor: 'rgba(75, 192, 192, 0.6)', // Green
                                    borderColor: 'rgba(75, 192, 192, 1)',
                                    borderWidth: 1
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        // Format as currency
                                        callback: function(value) {
                                            return '₹' + value.toLocaleString();
                                        }
                                    }
                                }
                            },
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            if (context.parsed.y !== null) {
                                                label += '₹' + context.parsed.y.toLocaleString();
                                            }
                                            return label;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }

                // --- NEW SCRIPT: Auto-fill form based on selected month/year ---
                const allRecords = <?php echo json_encode($expense_records); ?>;
                const monthSelect = document.getElementById('month');
                const yearInput = document.getElementById('year');
                const snapshotButton = document.getElementById('snapshot-button');
                const formFields = [
                    'monthly_income', 'petrol', 'kitchen_food', 'out_food', 'clothes', 
                    'wifi', 'home', 'milk', 'saving1', 'saving2', 'sip', 
                    'insurance', 'protein', 'creatine'
                ];

                const updateFormForPeriod = () => {
                    const month = monthSelect.value;
                    const year = yearInput.value;
                    
                    const record = allRecords.find(r => r.month == month && r.year == year);

                    if (record) {
                        // Record found, populate form
                        formFields.forEach(field => {
                            const input = document.getElementById(field);
                            if (input) {
                                input.value = record[field] || 0;
                            }
                        });
                        snapshotButton.innerText = 'Update Snapshot';
                        snapshotButton.classList.remove('bg-indigo-600', 'hover:bg-indigo-500');
                        snapshotButton.classList.add('bg-green-600', 'hover:bg-green-500');
                    } else {
                        // No record found, clear form
                        formFields.forEach(field => {
                            const input = document.getElementById(field);
                            if (input) {
                                input.value = ''; // Or set to 0.00 if you prefer
                            }
                        });
                        snapshotButton.innerText = 'Save Snapshot';
                        snapshotButton.classList.remove('bg-green-600', 'hover:bg-green-500');
                        snapshotButton.classList.add('bg-indigo-600', 'hover:bg-indigo-500');
                    }
                };

                // Add event listeners
                monthSelect.addEventListener('change', updateFormForPeriod);
                yearInput.addEventListener('change', updateFormForPeriod);

                // Run once on page load to check the default selected period
                updateFormForPeriod();
            });
            </script>
            <?php endif; ?>

<?php
            break;

        case 'daily_tracker':
            // These categories are for the daily expense form dropdown
            $daily_categories = [
                'Food' => ['kitchen_food', 'out_food', 'milk'],
                'Transport' => ['petrol'],
                'Bills' => ['wifi', 'home', 'insurance'],
                'Personal' => ['clothes'],
                'Gym' => ['protein', 'creatine'],
                'Other' => ['other'] // Add a generic "other"
            ];
?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Add Daily Expense -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-lg font-semibold mb-4 border-b pb-2">Add Daily Expense</h3>
                        <form action="index.php?page=add_daily_expense" method="POST" class="space-y-4">
                            <div>
                                <label for="date" class="block text-sm font-medium text-gray-700">Date</label>
                                <input type="date" name="date" id="date" value="<?php echo date('Y-m-d'); ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-2 py-1.5">
                            </div>
                            
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700">Amount</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-gray-500 sm:text-sm">₹</span>
                                    </div>
                                    <input type="number" step="0.01" min="0" name="amount" id="amount" required class="block w-full rounded-md border-gray-300 pl-7 pr-2 py-1.5 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="0.00">
                                </div>
                            </div>
                            
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                                <select id="category" name="category" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select a category...</option>
                                    <?php foreach ($daily_categories as $group => $items): ?>
                                        <optgroup label="<?php echo $group; ?>">
                                            <?php foreach ($items as $item): ?>
                                                <option value="<?php echo $item; ?>"><?php echo ucfirst(str_replace('_', ' ', $item)); ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700">Description (Optional)</label>
                                <input type="text" name="description" id="description" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-2 py-1.5" placeholder="e.g., Coffee with friends">
                            </div>

                            <div>
                                <button type="submit" class="w-full flex justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                                    Add Expense
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Summary & History -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- This Month's Summary -->
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-lg font-semibold mb-4 border-b pb-2">Summary for <?php echo date('F Y'); ?></h3>
                        <?php if (!$monthly_budget): ?>
                            <p class="text-gray-500">
                                Please go to the <a href="index.php?page=dashboard" class="text-indigo-600 hover:underline">Monthly Snapshot</a> page and save your income and savings goals for this month to activate the tracker.
                            </p>
                        <?php else: ?>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <div class="text-sm font-medium text-gray-500">Income</div>
                                    <div class="text-2xl font-semibold text-gray-900">₹<?php echo number_format($summary['income'], 2); ?></div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-500">Budgeted Savings</div>
                                    <div class="text-2xl font-semibold text-gray-900">₹<?php echo number_format($summary['budgeted_savings'], 2); ?></div>
                                </div>
                                <div class="col-span-2 border-t pt-4">
                                    <div class="text-sm font-medium text-gray-500">Available to Spend</div>
                                    <div class="text-2xl font-semibold text-blue-600">₹<?php echo number_format($summary['available_to_spend'], 2); ?></div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-500">Total Spent</div>
                                    <div class="text-2xl font-semibold text-red-600">₹<?php echo number_format($summary['total_spent'], 2); ?></div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-500">Money Remaining</div>
                                    <div class="text-2xl font-semibold <?php echo $summary['money_remaining'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                        ₹<?php echo number_format($summary['money_remaining'], 2); ?>
                                    </div>
                                </div>
                            </div>
                            <!-- Progress Bar -->
                            <div>
                                <div class="flex justify-between text-sm font-medium text-gray-600 mb-1">
                                    <span>Spent</span>
                                    <span><?php echo number_format($summary['percent_spent'], 1); ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo max(0, min(100, $summary['percent_spent'])); ?>%"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- NEW: Daily Spending Chart -->
                    <?php if ($monthly_budget): ?>
                    
                    <!-- NEW: Filter Form for Daily Chart -->
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-lg font-semibold mb-4 border-b pb-2">Filter Transactions</h3>
                        <form action="index.php" method="GET" class="space-y-4">
                            <input type="hidden" name="page" value="daily_tracker">
                            <div>
                                <label for="filter_type" class="block text-sm font-medium text-gray-700">Filter By</label>
                                <select id="filter_type" name="filter_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <?php $current_filter = $_GET['filter_type'] ?? 'this_month'; ?>
                                    <option value="this_month" <?php echo ($current_filter === 'this_month' ? 'selected' : ''); ?>>This Month</option>
                                    <option value="last_30_days" <?php echo ($current_filter === 'last_30_days' ? 'selected' : ''); ?>>Last 30 Days</option>
                                    <option value="custom" <?php echo ($current_filter === 'custom' ? 'selected' : ''); ?>>Custom Date Range</option>
                                </select>
                            </div>
                            
                            <div id="custom-date-range" class="<?php echo ($current_filter === 'custom' ? '' : 'hidden'); ?> grid grid-cols-2 gap-4">
                                <?php 
                                    $start_date_val = $_GET['start_date'] ?? '';
                                    $end_date_val = $_GET['end_date'] ?? '';
                                ?>
                                <div>
                                    <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                                    <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date_val); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-2 py-1.5">
                                </div>
                                <div>
                                    <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                                    <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date_val); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-2 py-1.5">
                                </div>
                            </div>
                            
                            <div>
                                <button type="submit" class="w-full flex justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                                    Apply Filter
                                </button>
                            </div>
                        </form>
                        
                        <script>
                            // JS to show/hide custom date range
                            document.getElementById('filter_type').addEventListener('change', function() {
                                const customDateRange = document.getElementById('custom-date-range');
                                if (this.value === 'custom') {
                                    customDateRange.classList.remove('hidden');
                                } else {
                                    customDateRange.classList.add('hidden');
                                }
                            });
                        </script>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-lg font-semibold mb-4 border-b pb-2">Daily Spending (<?php echo $filter_title; // <-- Use new dynamic title ?>)</h3>
                        <div class="relative" style="height: 300px;">
                            <canvas id="dailySpendingChart"></canvas>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Recent Transactions -->
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-lg font-semibold mb-4 border-b pb-2">Recent Transactions (<?php echo $filter_title; // <-- Use new dynamic title ?>)</h3>
                        <?php if (empty($filtered_transactions)): // <-- Use filtered data ?>
                            <p class="text-gray-500">No daily expenses found for this period.</p>
                        <?php else: ?>
                            <ul class="divide-y divide-gray-200">
                                <?php foreach ($filtered_transactions as $tx): // <-- Use filtered data ?>
                                    <li class="py-3 flex justify-between items-center">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 capitalize"><?php echo htmlspecialchars(str_replace('_', ' ', $tx['category'])); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo date('D, M jS', strtotime($tx['date'])); ?> - <?php echo htmlspecialchars($tx['description']); ?></div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-semibold text-red-600">-₹<?php echo number_format($tx['amount'], 2); ?></div>
                                            <a href="index.php?page=delete_daily_expense&id=<?php echo $tx['id']; ?>" 
                                               class="text-xs text-gray-400 hover:text-red-600"
                                               onclick="return confirm('Are you sure you want to delete this expense?');">
                                                Delete
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- NEW: JavaScript for Daily Spending Chart -->
            <?php if ($monthly_budget): // Only show chart script if budget is set ?>
            <script>
            document.addEventListener('DOMContentLoaded', () => {
                const transactions = <?php echo json_encode($filtered_transactions ?? []); // <-- Use filtered data ?>;
                
                // --- NEW DYNAMIC CHART LOGIC ---
                const startDate = new Date('<?php echo $chart_start_date; ?>T00:00:00'); // Get dates from PHP
                const endDate = new Date('<?php echo $chart_end_date; ?>T00:00:00');
                
                const labels = [];
                const spendingByDay = new Map(); // Use a Map for easier date keying

                // 1. Initialize all days in the range with 0 spending
                // Use UTC dates to avoid timezone issues
                for (let d = new Date(startDate.getTime()); d.getTime() <= endDate.getTime(); d.setUTCDate(d.getUTCDate() + 1)) {
                    const dateString = d.toISOString().split('T')[0];
                    labels.push(dateString);
                    spendingByDay.set(dateString, 0);
                }

                // 2. Process transactions and sum spending by day
                transactions.forEach(tx => {
                    const dateString = tx.date; // Date from PHP is already Y-m-d
                    if (spendingByDay.has(dateString)) {
                         spendingByDay.set(dateString, (spendingByDay.get(dateString) || 0) + parseFloat(tx.amount));
                    }
                });

                // 3. Create the data array from the map values
                const data = Array.from(spendingByDay.values());
                
                // 4. Format labels for display (e.g., "Oct 25" or "25")
                let displayLabels = labels;
                // If the range is long, just show dates. If short, show more detail.
                if (labels.length > 15) {
                     displayLabels = labels.map(d => d.split('-')[2]); // Just the day number
                } else {
                     displayLabels = labels.map(d => {
                        const dateObj = new Date(d + 'T00:00:00');
                        return dateObj.toLocaleDateString('default', { month: 'short', day: 'numeric', timeZone: 'UTC' });
                     });
                }
                
                const ctxDaily = document.getElementById('dailySpendingChart');
                if (ctxDaily) {
                    new Chart(ctxDaily.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: displayLabels, // Use new formatted labels
                            datasets: [{
                                label: 'Daily Spending',
                                data: data,
                                backgroundColor: 'rgba(54, 162, 235, 0.6)', // Blue
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return '₹' + value.toLocaleString();
                                        }
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Date'
                                    }
                                }
                            },
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        title: function(context) {
                                            // Show the full date in the tooltip title
                                            return labels[context[0].dataIndex];
                                        },
                                        label: function(context) {
                                            return ' Spending: ₹' + context.parsed.y.toLocaleString();
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            });
            </script>
            <?php endif; ?>
<?php
            break;

        case 'db_error':
?>
            <div class="rounded-md bg-red-100 p-4">
                <h3 class="text-lg font-medium text-red-800">Database Connection Error</h3>
                <div class="mt-2 text-sm text-red-700">
                    <p>The application could not connect to the database. Please check the following:</p>
                    <ul class="list-disc space-y-1 pl-5 mt-2">
                        <li>Ensure your MySQL server (e.g., in XAMPP or MAMP) is running.</li>
                        <li>Verify the database credentials (DB_HOST, DB_NAME, DB_USER, DB_PASS) at the top of <strong>index.php</strong> are correct.</li>
                        <li>Make sure the database '<?php echo DB_NAME; ?>' exists and you have imported the <strong>schema.sql</strong> and <strong>schema-update.sql</strong> files.</li>
                    </ul>
                </div>
            </div>
<?php
            break;

        case '404':
?>
            <div class="text-center">
                <h2 class="text-6xl font-bold text-indigo-600">404</h2>
                <p class="text-2xl font-semibold text-gray-800 mt-4">Page Not Found</p>
                <p class="text-gray-600 mt-2">Sorry, the page you are looking for does not exist.</p>
                <a href="index.php?page=dashboard" class="mt-6 inline-block rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Go to Dashboard
                </a>
            </div>
<?php
            break;
    }
}


// --- HTML RENDERING ---

// Set the title based on the page
$title_map = [
    'login' => 'Login',
    'register' => 'Register',
    'dashboard' => 'Monthly Snapshot',
    'daily_tracker' => 'Daily Expense Tracker',
    'db_error' => 'Database Error',
    '404' => 'Not Found',
];
$title = $title_map[$page] ?? 'Finance Tracker';

// Render the header
render_header($title);

// Render the main page content
$data_to_pass = [
    'expense_records' => $expense_records ?? [],
    'filter' => $filter ?? 'this_year', // <-- Pass dashboard filter
    'current_month_transactions' => $current_month_transactions ?? [], // <-- Pass summary data
    'filtered_transactions' => $filtered_transactions ?? [], // <-- Pass filtered data
    'filter_title' => $filter_title ?? 'for ' . date('F Y'), // <-- Pass titles
    'chart_start_date' => $chart_start_date ?? date('Y-m-01'), // <-- Pass chart dates
    'chart_end_date' => $chart_end_date ?? date('Y-m-t'), // <-- Pass chart dates
    'monthly_budget' => $monthly_budget ?? null,
    'summary' => $summary ?? [],
    // Add any other data the page functions might need
];
render_page_content($page, $pdo, $data_to_pass);

// Render the footer

