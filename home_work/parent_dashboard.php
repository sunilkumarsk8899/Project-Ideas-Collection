<?php
// parent_dashboard.php
include 'auth_check.php';
require_login('parent'); // Only parents allowed
include 'db_connect.php';

$parent_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f9f9f9; }
        .header { background-color: #17a2b8; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 1.5rem; }
        .header a { color: white; text-decoration: none; background-color: #e63946; padding: 0.5rem 1rem; border-radius: 4px; }
        .container { max-width: 900px; margin: 2rem auto; padding: 1rem; }
        .card { background-color: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .card-header { padding: 1rem 1.5rem; border-bottom: 1px solid #eee; }
        .card-header h2 { margin: 0; }
        .card-body { padding: 1.5rem; }
        .hw-list table { width: 100%; border-collapse: collapse; }
        .hw-list th, .hw-list td { padding: 0.75rem; border: 1px solid #ddd; text-align: left; }
        .hw-list th { background-color: #f4f4f4; }
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
        <div class="card">
            <div class="card-header">
                <h2>Your Child's Homework Status</h2>
            </div>
            <div class="card-body hw-list">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Homework</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // This query finds all students linked to this parent,
                        // then finds all submissions for those students.
                        $sql = "SELECT 
                                    u.full_name AS student_name,
                                    h.title, h.due_date,
                                    s.is_done
                                FROM users u
                                JOIN submissions s ON u.user_id = s.student_id
                                JOIN homework h ON s.hw_id = h.hw_id
                                WHERE u.parent_id = $parent_id";
                        
                        $result = $conn->query($sql);
                        
                        if ($result && $result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $status = $row['is_done'] ? '<span class="status-done">Done</span>' : '<span class="status-pending">Pending</span>';
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['due_date']) . "</td>";
                                echo "<td>" . $status . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4'>No homework submissions found for your child.</td></tr>";
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
