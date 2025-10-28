<?php
// teacher_dashboard.php
include 'auth_check.php';
require_login('teacher'); // Only teachers allowed
include 'db_connect.php';

$teacher_id = $_SESSION['user_id'];
$message = $_GET['message'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f9f9f9; }
        .header { background-color: #0056b3; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 1.5rem; }
        .header a { color: white; text-decoration: none; background-color: #e63946; padding: 0.5rem 1rem; border-radius: 4px; }
        .container { max-width: 1000px; margin: 2rem auto; padding: 1rem; }
        .card { background-color: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .card-header { padding: 1rem 1.5rem; border-bottom: 1px solid #eee; }
        .card-header h2 { margin: 0; }
        .card-body { padding: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        .form-group input, .form-group textarea { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        textarea { resize: vertical; min-height: 100px; }
        .btn { padding: 0.75rem 1.5rem; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        .btn:hover { background-color: #0056b3; }
        .message { padding: 1rem; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 1rem; }
        .hw-list table { width: 100%; border-collapse: collapse; }
        .hw-list th, .hw-list td { padding: 0.75rem; border: 1px solid #ddd; text-align: left; }
        .hw-list th { background-color: #f4f4f4; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
        <a href="logout.php">Logout</a>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Assign New Homework Card -->
        <div class="card">
            <div class="card-header">
                <h2>Assign New Homework</h2>
            </div>
            <div class="card-body">
                <form action="assign_homework.php" method="post">
                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="due_date">Due Date:</label>
                        <input type="date" id="due_date" name="due_date" required>
                    </div>
                    <button type="submit" class="btn">Assign</button>
                </form>
            </div>
        </div>

        <!-- View Homework Status Card -->
        <div class="card">
            <div class="card-header">
                <h2>Homework Status</h2>
            </div>
            <div class="card-body hw-list">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Due Date</th>
                            <th>Student</th>
                            <th>Status</th>
                            <th>Submitted On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // This query is complex. It joins homework, submissions, and users (students).
                        // It gets all submissions for homework assigned by THIS teacher.
                        $sql = "SELECT 
                                    h.title, h.due_date, 
                                    u.full_name AS student_name, 
                                    s.is_done, s.submitted_at
                                FROM homework h
                                JOIN submissions s ON h.hw_id = s.hw_id
                                JOIN users u ON s.student_id = u.user_id
                                WHERE h.teacher_id = $teacher_id";
                        
                        $result = $conn->query($sql);
                        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $status = $row['is_done'] ? 'Done' : 'Pending';
                                $submitted = $row['is_done'] ? $row['submitted_at'] : 'N/A';
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['due_date']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                                echo "<td>" . $status . "</td>";
                                echo "<td>" . $submitted . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5'>No submissions yet.</td></tr>";
                        }
                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
