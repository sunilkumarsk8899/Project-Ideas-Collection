<?php
// assign_homework.php
include 'auth_check.php';
require_login('teacher');
include 'db_connect.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $teacher_id = $_SESSION['user_id'];
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $due_date = $conn->real_escape_string($_POST['due_date']);

    if (!empty($title) && !empty($description) && !empty($due_date)) {
        $conn->begin_transaction();
        try {
            // 1. Insert the homework
            $sql_hw = "INSERT INTO homework (teacher_id, title, description, due_date) VALUES ('$teacher_id', '$title', '$description', '$due_date')";
            if (!$conn->query($sql_hw)) {
                throw new Exception("Error creating homework: " . $conn->error);
            }
            
            $hw_id = $conn->insert_id; // Get the ID of the homework just created

            // 2. Find all students
            $sql_students = "SELECT user_id FROM users WHERE role = 'student'";
            $result_students = $conn->query($sql_students);

            if ($result_students->num_rows > 0) {
                // 3. Create a 'pending' submission for every student
                $sql_sub_base = "INSERT INTO submissions (hw_id, student_id, is_done) VALUES ";
                $values = [];
                while ($student = $result_students->fetch_assoc()) {
                    $student_id = $student['user_id'];
                    $values[] = "('$hw_id', '$student_id', 0)";
                }
                $sql_sub = $sql_sub_base . implode(", ", $values);

                if (!$conn->query($sql_sub)) {
                    throw new Exception("Error creating submissions: " . $conn->error);
                }
            }
            
            // If all queries were successful, commit the transaction
            $conn->commit();
            $message = "Homework assigned successfully to all students!";

        } catch (Exception $e) {
            // An error occurred, roll back changes
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
        }

    } else {
        $message = "All fields are required.";
    }
}

// Redirect back to dashboard with a message
header("Location: teacher_dashboard.php?message=" . urlencode($message));
exit;
?>
