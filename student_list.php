<?php
session_start();

// Redirect if admin not logged in
if (!isset($_SESSION['admin_user'])) {
    header("Location: admin_login.php");
    exit;
}

// Database connection
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "new_registration_data";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get student list by course, department, batch
$selected_course = isset($_GET['course']) ? $_GET['course'] : '';
$selected_department = isset($_GET['dept']) ? $_GET['dept'] : '';
$selected_batch = isset($_GET['batch']) ? $_GET['batch'] : '';

$students = [];
if ($selected_course && $selected_department && $selected_batch) {
    $query = "SELECT student_id, first_name, last_name, gender, dob, batch, department, roll_number, institute_email, course, semester 
              FROM students_new_data 
              WHERE course = ? AND department = ? AND batch = ? 
              ORDER BY roll_number ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $selected_course, $selected_department, $selected_batch);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student List - <?php echo htmlspecialchars($selected_course . " | " . $selected_department . " | Batch " . $selected_batch); ?></title>
    <style>
        body { background-color: #f0f4f8; }
        .container { padding: 30px; }
        .back-btn {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
            text-decoration: none;
        }
        .back-btn:hover { background: #5a6268; }
        .batch-header {
            background: #003366;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .student-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 28px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.09);
        }
        .student-table th, .student-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }
        .student-table th {
            background: #0066cc;
            color: white;
        }
        .student-table tr:last-child td { border-bottom: none; }
        .student-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .total-header {
            font-size: 18px;
            font-weight: bold;
            color: #003366;
            background: #e3f1ff;
            padding: 14px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 18px;
        }
        .no-students {
            text-align: center; padding: 40px; color: #666; font-size: 18px; background: white; border-radius: 10px; margin-top: 28px;
        }
    </style>
</head>
<body>
<div class="container">
    <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
    <div class="batch-header">
        <h1><?php echo htmlspecialchars($selected_course); ?> | <?php echo htmlspecialchars($selected_department); ?> | Batch <?php echo htmlspecialchars($selected_batch); ?></h1>
    </div>

    <?php if (!empty($students)): ?>
        <div class="total-header">Total Students: <?php echo count($students); ?></div>
        <table class="student-table">
            <tr>
                
                <th>Roll No</th>
                <th>Name</th>
                <th>Gender</th>
                <th>Email</th>
                <th>Date of Birth</th>
                <th>Semester</th>
            </tr>
            <?php foreach ($students as $student): ?>
                <tr>
                  
                    <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($student['gender']); ?></td>
                    <td><?php echo htmlspecialchars($student['institute_email']); ?></td>
                    <td><?php echo htmlspecialchars($student['dob']); ?></td>
                    <td><?php echo htmlspecialchars($student['semester']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <div class="no-students">
            <h2>No students found in <?php echo htmlspecialchars($selected_department); ?> department, Batch <?php echo htmlspecialchars($selected_batch); ?>.</h2>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
