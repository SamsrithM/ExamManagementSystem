<?php
session_start();

// Redirect if admin not logged in
if (!isset($_SESSION['admin_user'])) {
    header("Location: admin_login.php");
    exit;
}

// Database connection for student data
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "new_registration_data";

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

    /* Sidebar */
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

    .sidebar a:hover, .sidebar a.active {
        background-color: #0059b3;
    }

    /* Main Content */
    .main {
        margin-left: 230px;
        padding: 40px;
        width: 100%;
        animation: fadeIn 0.6s ease-in-out;
    }

    @keyframes fadeIn {
        from {opacity: 0;}
        to {opacity: 1;}
    }

    h1 {
        color: #003366;
        margin-bottom: 20px;
    }

    /* Dashboard Buttons */
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
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        transition: transform 0.2s;
    }

    .move-btn:hover {
        transform: scale(1.05);
    }

    @keyframes move {
        0% { left: 0; }
        100% { left: 20px; }
    }

    /* Student List Grid */
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
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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

    /* Student Cards */
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
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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

    /* Top-right Logout */
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

</style>
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="#" id="dashboardBtn" class="active">🏠 Dashboard</a>
    <a href="#" id="studentListBtn">👥 Student List</a>
    <a href="#">📚 Courses</a>
    <a href="admin_upload_photo.php">👤 View Profile</a>
    <a href="admin_logout.php" class="logout">🚪 Logout</a>
</div>

<div class="main">
    <div id="dashboardSection">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['admin_user']); ?></h1>
        <div class="dashboard-btns">
            <button class="move-btn">🎓 Assign Invigilator Duties</button>
            <button class="move-btn">🪑 Seating Arrangement</button>
        </div>
    </div>

    <div id="studentListSection" class="student-list">
        <?php if (empty($db_error) && !empty($programs)): ?>
            <?php foreach ($programs as $program): ?>
                <div class="student-section">
                    <h3>🎓 <?php echo htmlspecialchars($program); ?></h3>
                    <?php foreach ($departments as $dept): ?>
                        <div class="dept-group">
                            <h4><?php echo htmlspecialchars($dept); ?></h4>
                            <?php foreach ($batches as $batch): ?>
                                <a href="?program=<?php echo urlencode($program); ?>&dept=<?php echo urlencode($dept); ?>&batch=<?php echo $batch; ?>" class="dept-btn"><?php echo $batch; ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                <h3 style="color: #856404; margin-bottom: 15px;">⚠️ No Student Data Found</h3>
                <p style="color: #856404; margin-bottom: 10px;">No students have been registered yet.</p>
                <p style="color: #856404; font-size: 14px; margin-bottom: 15px;">To add students:</p>
                <ol style="color: #856404; font-size: 14px; text-align: left; max-width: 500px; margin: 0 auto;">
                    <li>Use the registration system to register students</li>
                    <li>Make sure to fill in program, department, and batch information</li>
                    <li>Students will appear here once registered</li>
                </ol>
                <p style="margin-top: 20px;"><a href="../New_registration/registration_page.php" style="background: #0066cc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Go to Registration Page</a></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Student Table Section -->
    <div id="studentCardsSection" class="student-cards">
        <?php if (isset($_GET['program']) && isset($_GET['dept']) && isset($_GET['batch'])): ?>
            <button class="back-btn" onclick="goBack()">← Back to Program List</button>
            <div class="batch-header">
                <h2><?php echo htmlspecialchars($selected_program); ?> - <?php echo htmlspecialchars($selected_department); ?> - Batch <?php echo htmlspecialchars($selected_batch); ?></h2>
                <p>Total Students: <?php echo count($students); ?></p>
            </div>
            
            <?php if (!empty($db_error)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <h3 style="color: #856404; margin-bottom: 15px;">⚠️ Database Setup Required</h3>
                    <p style="color: #856404; margin-bottom: 10px;">The student registration database is not set up yet.</p>
                    <p style="color: #856404; font-size: 14px; margin-bottom: 15px;">To fix this:</p>
                    <ol style="color: #856404; font-size: 14px; text-align: left; max-width: 500px; margin: 0 auto;">
                        <li>Run the SQL file: <code>New_registration/registration_database.sql</code></li>
                        <li>Or register students through the registration system</li>
                        <li>Make sure MySQL is running in XAMPP</li>
                    </ol>
                </div>
            <?php elseif (!empty($students)): ?>
                <div style="grid-column: 1 / -1; overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        <thead style="background: #003366; color: white;">
                            <tr>
                                <th style="padding: 15px; text-align: left;">S.No</th>
                                <th style="padding: 15px; text-align: left;">Name</th>
                                <th style="padding: 15px; text-align: left;">Roll Number</th>
                                <th style="padding: 15px; text-align: left;">Email</th>
                                <th style="padding: 15px; text-align: left;">Program</th>
                                <th style="padding: 15px; text-align: left;">Semester</th>
                                <th style="padding: 15px; text-align: left;">Gender</th>
                                <th style="padding: 15px; text-align: left;">DOB</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $index => $student): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 12px 15px;"><?php echo $index + 1; ?></td>
                                    <td style="padding: 12px 15px; font-weight: bold; color: #003366;"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td style="padding: 12px 15px;"><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                    <td style="padding: 12px 15px;"><?php echo htmlspecialchars($student['institute_email']); ?></td>
                                    <td style="padding: 12px 15px;"><?php echo htmlspecialchars($student['course']); ?></td>
                                    <td style="padding: 12px 15px;"><?php echo htmlspecialchars($student['semester']); ?></td>
                                    <td style="padding: 12px 15px;"><?php echo ucfirst(htmlspecialchars($student['gender'])); ?></td>
                                    <td style="padding: 12px 15px;"><?php echo htmlspecialchars($student['dob']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <h3 style="color: #666; margin-bottom: 15px;">No Students Found</h3>
                    <p style="color: #888;">No students found for <?php echo htmlspecialchars($selected_program); ?> - <?php echo htmlspecialchars($selected_department); ?> - Batch <?php echo htmlspecialchars($selected_batch); ?></p>
                    <p style="color: #888; font-size: 14px; margin-top: 10px;">Make sure students are registered in the system with the correct program, department and batch information.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    const dashboardBtn = document.getElementById('dashboardBtn');
    const studentListBtn = document.getElementById('studentListBtn');
    const dashboardSection = document.getElementById('dashboardSection');
    const studentListSection = document.getElementById('studentListSection');
    const studentCardsSection = document.getElementById('studentCardsSection');

    // Check if student table should be shown
    <?php if (isset($_GET['program']) && isset($_GET['dept']) && isset($_GET['batch'])): ?>
        showStudentCards();
    <?php endif; ?>

    function showStudentCards() {
        dashboardSection.style.display = 'none';
        studentListSection.style.display = 'none';
        studentCardsSection.style.display = 'grid';
        studentListBtn.classList.add('active');
        dashboardBtn.classList.remove('active');
    }

    function goBack() {
        dashboardSection.style.display = 'none';
        studentListSection.style.display = 'grid';
        studentCardsSection.style.display = 'none';
        studentListBtn.classList.add('active');
        dashboardBtn.classList.remove('active');
    }

    dashboardBtn.addEventListener('click', () => {
        dashboardSection.style.display = 'block';
        studentListSection.style.display = 'none';
        studentCardsSection.style.display = 'none';
        dashboardBtn.classList.add('active');
        studentListBtn.classList.remove('active');
    });

    studentListBtn.addEventListener('click', () => {
        dashboardSection.style.display = 'none';
        studentListSection.style.display = 'grid';
        studentCardsSection.style.display = 'none';
        studentListBtn.classList.add('active');
        dashboardBtn.classList.remove('active');
    });
</script>

</body>
</html>
