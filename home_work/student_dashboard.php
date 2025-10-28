<?php
// student_dashboard.php
include 'auth_check.php';
require_login('student'); // Only students allowed
include 'db_connect.php';

$student_id = $_SESSION['user_id'];
$message = $_GET['message'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f9f9f9; }
        .header { background-color: #28a745; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 1.5rem; }
        .header a { color: white; text-decoration: none; background-color: #e63946; padding: 0.5rem 1rem; border-radius: 4px; }
        .container { max-width: 900px; margin: 2rem auto; padding: 1rem; }
        .card { background-color: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .card-header { padding: 1rem 1.5rem; border-bottom: 1px solid #eee; }
        .card-header h2 { margin: 0; }
        .card-body { padding: 1.5rem; }
        .message { padding: 1rem; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 1rem; }
        .hw-list table { width: 100%; border-collapse: collapse; }
        .hw-list th, .hw-list td { padding: 0.75rem; border: 1px solid #ddd; text-align: left; }
        .hw-list th { background-color: #f4f4f4; }
        .btn-done { padding: 0.5rem 1rem; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-done:hover { background-color: #218838; }
        .status-done { color: green; font-weight: bold; }
        .status-pending { color: orange; font-weight: bold; }
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

        <div class="card">
            <div class="card-header">
                <h2>Your Homework</h2>
            </div>
            <div class="card-body hw-list">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Assigned By</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // This query gets all homework and joins this student's submission status
                        $sql = "SELECT 
                                    h.hw_id, h.title, h.description, h.due_date,
                                    u.full_name AS teacher_name,
                                    s.is_done
                                FROM homework h
                                JOIN users u ON h.teacher_id = u.user_id
                                LEFT JOIN submissions s ON h.hw_id = s.hw_id AND s.student_id = $student_id";
                        
                        $result = $conn->query($sql);
                        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $status = $row['is_done'] ? '<span class="status-done">Done</span>' : '<span class="status-pending">Pending</span>';
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                echo "<td>" . nl2br(htmlspecialchars($row['description'])) . "</td>";
                                echo "<td>" . htmlspecialchars($row['teacher_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['due_date']) . "</td>";
                                echo "<td>" . $status . "</td>";
                                echo "<td>";
                                if (!$row['is_done']) {
                                    echo "<a href='submit_homework.php?hw_id=" . $row['hw_id'] . "' class='btn-done'>Mark as Done</a>";
                                } else {
                                    echo "Completed";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No homework assigned yet!</td></tr>";
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
