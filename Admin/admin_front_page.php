<?php
session_start();

// Redirect if admin not logged in
if (!isset($_SESSION['admin_user'])) {
    header("Location: admin_login.php");
    exit;
}

// Database connection using Render environment variables
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'new_registration_data';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    $db_error = "Database connection failed: " . $conn->connect_error;
    $students = [];
    $selected_department = '';
    $selected_batch = '';
} else {
    $db_error = '';
}

// Get all programs, departments, and batches from database
$programs = [];
$departments = [];
$batches = [];

if (empty($db_error)) {
    // Get unique programs
    $result = $conn->query("SELECT DISTINCT course FROM students_new_data ORDER BY course");
    while ($row = $result->fetch_assoc()) {
        $programs[] = $row['course'];
    }
    
    // Get unique departments
    $result = $conn->query("SELECT DISTINCT department FROM students_new_data ORDER BY department");
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row['department'];
    }
    
    // Get unique batches
    $result = $conn->query("SELECT DISTINCT batch FROM students_new_data ORDER BY batch");
    while ($row = $result->fetch_assoc()) {
        $batches[] = $row['batch'];
    }
}

// Get students by program, department and batch
$students = [];
$selected_program = '';
$selected_department = '';
$selected_batch = '';

if (isset($_GET['program']) && isset($_GET['dept']) && isset($_GET['batch']) && empty($db_error)) {
    $selected_program = $_GET['program'];
    $selected_department = $_GET['dept'];
    $selected_batch = $_GET['batch'];
    
    $query = "SELECT student_id, first_name, last_name, gender, dob, batch, department, roll_number, institute_email, course, semester 
              FROM students_new_data 
              WHERE course = ? AND department = ? AND batch = ? 
              ORDER BY roll_number ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $selected_program, $selected_department, $selected_batch);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
}

if (!empty($conn)) {
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<style>
    * {
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
    }

    body {
        background-color: #f0f4f8;
        display: flex;
        min-height: 100vh;
    }

    .sidebar {
        width: 230px;
        background-color: #003366;
        color: white;
        padding-top: 30px;
        display: flex;
        flex-direction: column;
        align-items: center;
        position: fixed;
        height: 100%;
    }

    .sidebar h2 {
        margin-bottom: 30px;
        font-size: 22px;
    }

    .sidebar a {
        text-decoration: none;
        color: white;
        display: block;
        width: 80%;
        padding: 12px;
        border-radius: 8px;
        margin: 6px 0;
        text-align: center;
        transition: 0.3s;
        font-size: 16px;
    }

    .sidebar a:hover,
    .sidebar a.active {
        background-color: #0059b3;
    }

    .main {
        margin-left: 230px;
        padding: 40px;
        width: 100%;
        animation: fadeIn 0.6s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    h1 {
        color: #003366;
        margin-bottom: 20px;
    }

    .dashboard-btns {
        display: flex;
        justify-content: center;
        gap: 30px;
        margin-top: 50px;
        flex-wrap: wrap;
    }

    .move-btn {
        padding: 18px 35px;
        border: none;
        border-radius: 50px;
        font-size: 18px;
        color: white;
        background: linear-gradient(90deg, #0066cc, #0099ff);
        cursor: pointer;
        position: relative;
        animation: move 3s infinite alternate ease-in-out;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        transition: transform 0.2s;
    }

    .move-btn:hover {
        transform: scale(1.05);
    }

    @keyframes move {
        0% {
            left: 0;
        }

        100% {
            left: 20px;
        }
    }

    .student-list {
        display: none;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-top: 30px;
    }

    .student-section {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    .student-section h3 {
        color: #003366;
        margin-bottom: 20px;
        font-size: 20px;
        text-align: center;
        border-bottom: 2px solid #0066cc;
        padding-bottom: 10px;
    }

    .dept-group {
        margin-bottom: 20px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #0066cc;
    }

    .dept-group h4 {
        color: #003366;
        margin-bottom: 10px;
        font-size: 16px;
        text-align: center;
    }

    .dept-group .dept-btn {
        display: inline-block;
        width: 60px;
        margin: 5px;
        padding: 8px 12px;
        border: none;
        border-radius: 6px;
        background-color: #0066cc;
        color: white;
        font-size: 13px;
        cursor: pointer;
        transition: 0.3s;
        text-decoration: none;
        text-align: center;
    }

    .dept-group .dept-btn:hover {
        background-color: #004c99;
        transform: scale(1.05);
    }

    .student-cards {
        display: none;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 30px;
        padding: 20px;
    }

    .student-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        padding: 20px;
        text-align: center;
        transition: transform 0.3s ease;
    }

    .student-card:hover {
        transform: translateY(-5px);
    }

    .student-photo {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        margin: 0 auto 15px;
        border: 3px solid #0066cc;
    }

    .student-name {
        font-size: 18px;
        font-weight: bold;
        color: #003366;
        margin-bottom: 8px;
    }

    .student-details {
        color: #666;
        font-size: 14px;
        line-height: 1.5;
    }

    .student-details div {
        margin: 5px 0;
    }

    .batch-header {
        background: #003366;
        color: white;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
    }

    .back-btn {
        background: #6c757d;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        margin-bottom: 20px;
    }

    .back-btn:hover {
        background: #5a6268;
    }

    .logout {
        position: absolute;
        right: 20px;
        top: 20px;
        color: white;
        text-decoration: none;
        background-color: crimson;
        padding: 8px 15px;
        border-radius: 6px;
        transition: 0.3s;
    }

    .logout:hover {
        background-color: darkred;
    }

    @media screen and (max-width: 1024px) {
        .dashboard-btns {
            flex-direction: column;
            align-items: center;
        }

        .student-list {
            grid-template-columns: repeat(2, 1fr);
        }

        .student-cards {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        }
    }

    @media screen and (max-width: 768px) {
        body {
            flex-direction: column;
        }

        .sidebar {
            width: 100%;
            height: auto;
            position: relative;
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: center;
            padding: 20px 10px;
        }

        .sidebar h2 {
            width: 100%;
            text-align: center;
            margin-bottom: 10px;
        }

        .sidebar a {
            width: auto;
            margin: 5px;
            font-size: 14px;
            padding: 8px 10px;
        }

        .main {
            margin-left: 0;
            padding: 20px;
        }

        .dashboard-btns {
            gap: 20px;
        }

        .student-list {
            grid-template-columns: 1fr;
        }

        .student-cards {
            grid-template-columns: 1fr;
        }

        .logout {
            position: static;
            margin-top: 10px;
        }
    }

    @media screen and (max-width: 480px) {
        h1 {
            font-size: 20px;
            text-align: center;
        }

        .move-btn {
            font-size: 16px;
            padding: 14px 25px;
        }

        .student-photo {
            width: 80px;
            height: 80px;
        }

        .student-name {
            font-size: 16px;
        }

        .student-details {
            font-size: 13px;
        }

        .back-btn {
            width: 100%;
            font-size: 14px;
        }
    }
</style>
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="#" id="dashboardBtn" class="active">🏠 Dashboard</a>
    <a href="Admin/student_list_admin.php" id="studentListBtn">👥 Student List</a>
    <a href="Admin/faculty_list.php" id="facultyListBtn">👨‍🏫 Faculty List</a>
    <a href="Admin/course_management.php">📚 Courses to Faculties</a>
    <a href="Admin/course_management_student.php">📚 Courses to Students</a>
    <a href="Admin/free_slots.php">🔓 Exam Slots</a>
    <a href="Admin/allocate_duties.php">🗂️ Allocate Duties</a>
    <a href="Admin/view_exam_slot.php">🔍 Selected Slots</a>
    <a href="Invigilation_duty/view_invigilation_duties_admin.php" id="viewDutiesBtn">📝 View Invigilation Duties</a>
    <a href="admin_upload_photo.php">👤 View Profile</a>
    <a href="Ems_start/frontpage.php" class="logout">🚪 Logout</a>
</div>

<div class="main">
    <div id="dashboardSection">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['admin_user']); ?></h1>
        <div class="dashboard-btns">
            <button class="move-btn" onclick="window.location.href='Invigilation_duty/assign_duties.php'">🎓 Assign Invigilator Duties</button>
            <button class="move-btn" onclick="window.location.href='Seating_arragement/seating_arrangement.php'">🪑 Seating Arrangement</button>
        </div>
    </div>

</body>
</html>