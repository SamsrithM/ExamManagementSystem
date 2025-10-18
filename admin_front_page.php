<?php
session_start();

// Redirect if admin not logged in
if (!isset($_SESSION['admin_user'])) {
    header("Location: admin_login.php");
    exit;
}

// Database connection for student and faculty data
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "new_registration_data";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    $db_error = "Database connection failed: " . $conn->connect_error;
    $students = [];
    $faculty = [];
    $courses = [];
    $selected_department = '';
    $selected_batch = '';
} else {
    $db_error = '';
}

// Separate connection for courses database
$courses_db_name = "course_registration_data";
$courses_conn = new mysqli($db_host, $db_user, $db_pass, $courses_db_name);
if ($courses_conn->connect_error) {
    $courses_db_error = "Courses database connection failed: " . $courses_conn->connect_error;
} else {
    $courses_db_error = '';
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

// Get faculty data
$faculty = [];
if (empty($db_error)) {
    $faculty_result = $conn->query("SELECT faculty_id, first_name, last_name, gender, email, department, designation FROM faculty_new_data ORDER BY first_name ASC");
    while ($row = $faculty_result->fetch_assoc()) {
        $faculty[] = $row;
    }
}

// Get courses data with faculty assignments
$courses = [];
if (empty($courses_db_error)) {
    $courses_result = $courses_conn->query("SELECT course_id, course_name, course_code, description, assigned_faculty_id, assigned_faculty_name, assigned_faculty_email, created_at FROM admin_courses ORDER BY course_name ASC");
    while ($row = $courses_result->fetch_assoc()) {
        $courses[] = $row;
    }
}

// Get faculty data for assignment dropdown
$faculty_for_assignment = [];
if (empty($db_error)) {
    $faculty_result = $conn->query("SELECT faculty_id, first_name, last_name, email FROM faculty_new_data ORDER BY first_name ASC");
    while ($row = $faculty_result->fetch_assoc()) {
        $faculty_for_assignment[] = $row;
    }
}

// Handle faculty assignment to courses
if (isset($_POST['assign_faculty']) && !empty($_POST['course_id']) && !empty($_POST['faculty_id']) && empty($courses_db_error)) {
    $course_id = intval($_POST['course_id']);
    $faculty_id = intval($_POST['faculty_id']);
    
    // Get faculty name and email
    $faculty_name = '';
    $faculty_email = '';
    foreach ($faculty_for_assignment as $faculty_member) {
        if ($faculty_member['faculty_id'] == $faculty_id) {
            $faculty_name = $faculty_member['first_name'] . ' ' . $faculty_member['last_name'];
            $faculty_email = $faculty_member['email'];
            break;
        }
    }
    
    if ($faculty_name) {
        $assign_stmt = $courses_conn->prepare("UPDATE admin_courses SET assigned_faculty_id = ?, assigned_faculty_name = ?, assigned_faculty_email = ? WHERE course_id = ?");
        $assign_stmt->bind_param("issi", $faculty_id, $faculty_name, $faculty_email, $course_id);
        
        if ($assign_stmt->execute()) {
            $course_message = '<div style="color: #2e7d32; background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0;">Faculty assigned successfully!</div>';
            // Refresh courses list
            $courses = [];
            $courses_result = $courses_conn->query("SELECT course_id, course_name, course_code, description, assigned_faculty_id, assigned_faculty_name, assigned_faculty_email, created_at FROM admin_courses ORDER BY course_name ASC");
            while ($row = $courses_result->fetch_assoc()) {
                $courses[] = $row;
            }
        } else {
            $course_message = '<div style="color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 5px; margin: 10px 0;">Error assigning faculty: ' . $assign_stmt->error . '</div>';
        }
        $assign_stmt->close();
    }
}

// Handle course addition and updates
$course_message = '';
$edit_course = null;
$edit_course_id = null;

// Check if editing a course
if (isset($_GET['edit_course']) && is_numeric($_GET['edit_course']) && empty($courses_db_error)) {
    $edit_course_id = intval($_GET['edit_course']);
    $edit_stmt = $courses_conn->prepare("SELECT course_id, course_name, course_code, description FROM admin_courses WHERE course_id = ?");
    $edit_stmt->bind_param("i", $edit_course_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    if ($edit_result->num_rows > 0) {
        $edit_course = $edit_result->fetch_assoc();
    }
    $edit_stmt->close();
}

// Handle course deletion
if (isset($_GET['delete_course']) && is_numeric($_GET['delete_course']) && empty($courses_db_error)) {
    $delete_course_id = intval($_GET['delete_course']);
    $delete_stmt = $courses_conn->prepare("DELETE FROM admin_courses WHERE course_id = ?");
    $delete_stmt->bind_param("i", $delete_course_id);
    if ($delete_stmt->execute()) {
        $course_message = '<div style="color: #2e7d32; background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0;">Course deleted successfully!</div>';
        // Refresh courses list
        $courses = [];
        $courses_result = $courses_conn->query("SELECT course_id, course_name, course_code, description, created_at FROM admin_courses ORDER BY course_name ASC");
        while ($row = $courses_result->fetch_assoc()) {
            $courses[] = $row;
        }
    } else {
        $course_message = '<div style="color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 5px; margin: 10px 0;">Error deleting course: ' . $delete_stmt->error . '</div>';
    }
    $delete_stmt->close();
}

// Handle course form submission (add or update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_course']) || isset($_POST['update_course'])) && empty($courses_db_error)) {
    $course_name = trim($_POST['course_name'] ?? '');
    $course_code = trim($_POST['course_code'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if ($course_name) {
        if (isset($_POST['update_course'])) {
            // Update existing course
            $update_course_id = intval($_POST['course_id']);
            
            // Check if course name/code already exists (excluding current course)
            $check_stmt = $courses_conn->prepare("SELECT course_id FROM admin_courses WHERE (course_name = ? OR course_code = ?) AND course_id != ?");
            $check_stmt->bind_param("ssi", $course_name, $course_code, $update_course_id);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                $course_message = '<div style="color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 5px; margin: 10px 0;">Course with this name or code already exists!</div>';
            } else {
                $update_stmt = $courses_conn->prepare("UPDATE admin_courses SET course_name = ?, course_code = ?, description = ? WHERE course_id = ?");
                $update_stmt->bind_param("sssi", $course_name, $course_code, $description, $update_course_id);
                
                if ($update_stmt->execute()) {
                    $course_message = '<div style="color: #2e7d32; background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0;">Course updated successfully!</div>';
                    // Refresh courses list
                    $courses = [];
                    $courses_result = $courses_conn->query("SELECT course_id, course_name, course_code, description, created_at FROM admin_courses ORDER BY course_name ASC");
                    while ($row = $courses_result->fetch_assoc()) {
                        $courses[] = $row;
                    }
                    $edit_course = null; // Clear edit mode
                } else {
                    $course_message = '<div style="color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 5px; margin: 10px 0;">Error updating course: ' . $update_stmt->error . '</div>';
                }
                $update_stmt->close();
            }
            $check_stmt->close();
        } else {
            // Add new course
            $check_stmt = $courses_conn->prepare("SELECT course_id FROM admin_courses WHERE course_name = ? OR course_code = ?");
            $check_stmt->bind_param("ss", $course_name, $course_code);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                $course_message = '<div style="color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 5px; margin: 10px 0;">Course with this name or code already exists!</div>';
            } else {
                $insert_stmt = $courses_conn->prepare("INSERT INTO admin_courses (course_name, course_code, description) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("sss", $course_name, $course_code, $description);
                
                if ($insert_stmt->execute()) {
                    $course_message = '<div style="color: #2e7d32; background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0;">Course added successfully!</div>';
                    // Refresh courses list
                    $courses = [];
                    $courses_result = $courses_conn->query("SELECT course_id, course_name, course_code, description, created_at FROM admin_courses ORDER BY course_name ASC");
                    while ($row = $courses_result->fetch_assoc()) {
                        $courses[] = $row;
                    }
                } else {
                    $course_message = '<div style="color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 5px; margin: 10px 0;">Error adding course: ' . $insert_stmt->error . '</div>';
                }
                $insert_stmt->close();
            }
            $check_stmt->close();
        }
    } else {
        $course_message = '<div style="color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 5px; margin: 10px 0;">Course name is required!</div>';
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
if (!empty($courses_conn)) {
    $courses_conn->close();
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

    /* Faculty List Styles */
    .faculty-list {
        animation: fadeIn 0.6s ease-in-out;
    }

    .faculty-list table {
        margin-top: 20px;
    }

    .faculty-list table th {
        background: #003366;
        color: white;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .faculty-list table tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    .faculty-list table tr:hover {
        background-color: #e3f2fd;
        transition: background-color 0.3s ease;
    }

</style>
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="#" id="dashboardBtn" class="active">🏠 Dashboard</a>
    <a href="#" id="studentListBtn">👥 Student List</a>
    <a href="#" id="facultyListBtn">👨‍🏫 Faculty List</a>
    <a href="#" id="coursesBtn">📚 Courses</a>
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

    <!-- Courses Section -->
    <div id="coursesSection" class="courses-section" style="display: none;">
        <h1>Course Management</h1>
        
        <!-- Add/Edit Course Form -->
        <div class="add-course-form" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 30px;">
            <h2 style="color: #003366; margin-bottom: 20px; display: flex; align-items: center;">
                <span style="margin-right: 10px;"><?php echo $edit_course ? '✏️' : '➕'; ?></span> 
                <?php echo $edit_course ? 'Edit Course' : 'Add New Course'; ?>
            </h2>
            
            <?php echo $course_message; ?>
            
            <form method="POST" style="display: grid; gap: 20px;">
                <?php if ($edit_course): ?>
                    <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($edit_course['course_id']); ?>">
                <?php endif; ?>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label for="course_name" style="display: block; margin-bottom: 8px; font-weight: bold; color: #003366;">Course Name *</label>
                        <input type="text" id="course_name" name="course_name" required 
                               value="<?php echo $edit_course ? htmlspecialchars($edit_course['course_name']) : ''; ?>"
                               style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;"
                               placeholder="e.g., Computer Science Engineering"
                               onfocus="this.style.borderColor='#0066cc'" 
                               onblur="this.style.borderColor='#e0e0e0'">
                    </div>
                    <div>
                        <label for="course_code" style="display: block; margin-bottom: 8px; font-weight: bold; color: #003366;">Course Code</label>
                        <input type="text" id="course_code" name="course_code" 
                               value="<?php echo $edit_course ? htmlspecialchars($edit_course['course_code']) : ''; ?>"
                               style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;"
                               placeholder="e.g., CSE"
                               onfocus="this.style.borderColor='#0066cc'" 
                               onblur="this.style.borderColor='#e0e0e0'">
                    </div>
                </div>
                <div>
                    <label for="description" style="display: block; margin-bottom: 8px; font-weight: bold; color: #003366;">Description</label>
                    <textarea id="description" name="description" rows="3" 
                              style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; transition: border-color 0.3s; resize: vertical;"
                              placeholder="Enter course description (optional)"
                              onfocus="this.style.borderColor='#0066cc'" 
                              onblur="this.style.borderColor='#e0e0e0'"><?php echo $edit_course ? htmlspecialchars($edit_course['description']) : ''; ?></textarea>
                </div>
                <div style="display: flex; gap: 15px; align-items: center;">
                    <button type="submit" name="<?php echo $edit_course ? 'update_course' : 'add_course'; ?>" 
                            style="background: linear-gradient(90deg, #0066cc, #0099ff); color: white; padding: 12px 30px; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: transform 0.2s;"
                            onmouseover="this.style.transform='scale(1.05)'" 
                            onmouseout="this.style.transform='scale(1)'">
                        <?php echo $edit_course ? '✏️ Update Course' : '➕ Add Course'; ?>
                    </button>
                    <?php if ($edit_course): ?>
                        <a href="?courses=1" style="background: #6c757d; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: bold; transition: transform 0.2s;"
                           onmouseover="this.style.transform='scale(1.05)'" 
                           onmouseout="this.style.transform='scale(1)'">
                            ❌ Cancel
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Courses List -->
        <div class="courses-list">
            <h2 style="color: #003366; margin-bottom: 20px; display: flex; align-items: center;">
                <span style="margin-right: 10px;">📚</span> Available Courses (<?php echo count($courses); ?>)
            </h2>
            
            <?php if (empty($courses_db_error) && !empty($courses)): ?>
                <div class="courses-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s; border-left: 4px solid #0066cc; position: relative;"
                             onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'; this.querySelector('.course-actions').style.opacity='1';"
                             onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'; this.querySelector('.course-actions').style.opacity='0';">
                            
                            <!-- Course Content -->
                            <div onclick="selectCourse('<?php echo htmlspecialchars($course['course_name']); ?>', '<?php echo htmlspecialchars($course['course_code']); ?>')" style="cursor: pointer;">
                                <h3 style="color: #003366; margin-bottom: 10px; font-size: 18px;"><?php echo htmlspecialchars($course['course_name']); ?></h3>
                                <?php if ($course['course_code']): ?>
                                    <p style="color: #666; margin-bottom: 8px; font-size: 14px;"><strong>Code:</strong> <?php echo htmlspecialchars($course['course_code']); ?></p>
                                <?php endif; ?>
                                
                                
                                <?php if ($course['description']): ?>
                                    <p style="color: #888; font-size: 14px; line-height: 1.4;"><?php echo htmlspecialchars($course['description']); ?></p>
                                <?php endif; ?>
                                <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #eee; color: #999; font-size: 12px;">
                                    Added: <?php echo date('M d, Y', strtotime($course['created_at'])); ?>
                                </div>
                            </div>
                            
                            <!-- Faculty Assignment Section -->
                            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef;">
                                <h4 style="color: #495057; margin-bottom: 12px; font-size: 14px; font-weight: bold;">
                                    👨‍🏫 Faculty Assignment
                                </h4>
                                
                                <?php if (!empty($course['assigned_faculty_name'])): ?>
                                    <div style="background: #d4edda; padding: 10px; border-radius: 6px; border-left: 4px solid #28a745; margin-bottom: 15px;">
                                        <p style="color: #155724; margin: 0; font-weight: bold; font-size: 14px;">
                                            ✅ Currently Assigned: <?php echo htmlspecialchars($course['assigned_faculty_name']); ?>
                                        </p>
                                        <?php if (!empty($course['assigned_faculty_email'])): ?>
                                            <p style="color: #155724; margin: 5px 0 0 0; font-size: 12px;">
                                                📧 Email: <?php echo htmlspecialchars($course['assigned_faculty_email']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div style="background: #fff3cd; padding: 10px; border-radius: 6px; border-left: 4px solid #ffc107; margin-bottom: 15px;">
                                        <p style="color: #856404; margin: 0; font-size: 14px;">
                                            ⚠️ No faculty assigned to this course
                                        </p>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" style="display: grid; gap: 12px;">
                                    <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                    
                                    <div>
                                        <label style="display: block; margin-bottom: 6px; font-weight: bold; color: #495057; font-size: 13px;">
                                            Select Faculty Member (Name & Email ID):
                                        </label>
                                        <select name="faculty_id" required 
                                                style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 6px; font-size: 14px; background: white; transition: border-color 0.3s;"
                                                onfocus="this.style.borderColor='#007bff'" 
                                                onblur="this.style.borderColor='#dee2e6'">
                                            <option value="">-- Choose Faculty Member (with Email ID) --</option>
                                            <?php foreach ($faculty_for_assignment as $faculty_member): ?>
                                                <option value="<?php echo $faculty_member['faculty_id']; ?>" 
                                                        <?php echo (!empty($course['assigned_faculty_id']) && $course['assigned_faculty_id'] == $faculty_member['faculty_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($faculty_member['first_name'] . ' ' . $faculty_member['last_name']); ?> 
                                                    <strong style="color: #007bff;">[<?php echo htmlspecialchars($faculty_member['email']); ?>]</strong>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" name="assign_faculty" 
                                            style="background: linear-gradient(90deg, #28a745, #20c997); color: white; padding: 12px 20px; border: none; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; transition: transform 0.2s; width: 100%;"
                                            onmouseover="this.style.transform='scale(1.02)'" 
                                            onmouseout="this.style.transform='scale(1)'">
                                        <?php echo !empty($course['assigned_faculty_name']) ? '🔄 Update Assignment' : '➕ Assign Faculty'; ?>
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Professional Action Menu -->
                            <div class="course-actions" style="position: absolute; top: 15px; right: 15px; opacity: 0; transition: opacity 0.3s ease;">
                                <div style="position: relative; display: inline-block;">
                                    <button onclick="event.stopPropagation(); toggleActionsMenu(this)" 
                                            style="background: rgba(255,255,255,0.9); border: 1px solid #ddd; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; color: #666; transition: all 0.3s;"
                                            onmouseover="this.style.background='#f8f9fa'; this.style.borderColor='#0066cc'; this.style.color='#0066cc'"
                                            onmouseout="this.style.background='rgba(255,255,255,0.9)'; this.style.borderColor='#ddd'; this.style.color='#666'"
                                            title="Course Actions">
                                        ⋯
                                    </button>
                                    
                                    <!-- Dropdown Menu -->
                                    <div class="actions-dropdown" style="position: absolute; top: 100%; right: 0; background: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); min-width: 140px; z-index: 1000; display: none; margin-top: 5px;">
                                        <a href="?edit_course=<?php echo $course['course_id']; ?>" 
                                           style="display: block; padding: 12px 16px; color: #333; text-decoration: none; border-bottom: 1px solid #f0f0f0; transition: background 0.2s; font-size: 14px;"
                                           onmouseover="this.style.background='#f8f9fa'; this.style.color='#0066cc'"
                                           onmouseout="this.style.background='white'; this.style.color='#333'">
                                            <span style="margin-right: 8px;">✏️</span> Edit Course
                                        </a>
                                        <a href="?delete_course=<?php echo $course['course_id']; ?>" 
                                           onclick="return confirm('Are you sure you want to delete this course? This action cannot be undone.');"
                                           style="display: block; padding: 12px 16px; color: #dc3545; text-decoration: none; transition: background 0.2s; font-size: 14px;"
                                           onmouseover="this.style.background='#fff5f5'"
                                           onmouseout="this.style.background='white'">
                                            <span style="margin-right: 8px;">🗑️</span> Delete Course
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif (!empty($courses_db_error)): ?>
                <div style="text-align: center; padding: 40px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <h3 style="color: #856404; margin-bottom: 15px;">⚠️ Courses Database Setup Required</h3>
                    <p style="color: #856404; margin-bottom: 10px;">The courses database is not set up yet.</p>
                    <p style="color: #856404; font-size: 14px; margin-bottom: 15px;">To fix this:</p>
                    <ol style="color: #856404; font-size: 14px; text-align: left; max-width: 500px; margin: 0 auto;">
                        <li>Run the SQL file: <code>Admin/courses_database.sql</code> in phpMyAdmin</li>
                        <li>Make sure MySQL is running in XAMPP</li>
                        <li>Create the <code>course_registration_data</code> database first</li>
                    </ol>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <h3 style="color: #856404; margin-bottom: 15px;">📚 No Courses Found</h3>
                    <p style="color: #856404; margin-bottom: 10px;">No courses have been added yet.</p>
                    <p style="color: #856404; font-size: 14px;">Use the form above to add your first course!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Faculty List Section -->
    <div id="facultyListSection" class="faculty-list" style="display: none;">
        <h1>Faculty List</h1>
        <?php if (empty($db_error) && !empty($faculty)): ?>
            <div style="overflow-x: auto; margin-top: 20px;">
                <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <thead style="background: #003366; color: white;">
                        <tr>
                            <th style="padding: 15px; text-align: left;">S.No</th>
                            <th style="padding: 15px; text-align: left;">Name</th>
                            <th style="padding: 15px; text-align: left;">Gender</th>
                            <th style="padding: 15px; text-align: left;">Email</th>
                            <th style="padding: 15px; text-align: left;">Department</th>
                            <th style="padding: 15px; text-align: left;">Designation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($faculty as $index => $faculty_member): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 12px 15px;"><?php echo $index + 1; ?></td>
                                <td style="padding: 12px 15px; font-weight: bold; color: #003366;"><?php echo htmlspecialchars($faculty_member['first_name'] . ' ' . $faculty_member['last_name']); ?></td>
                                <td style="padding: 12px 15px;"><?php echo ucfirst(htmlspecialchars($faculty_member['gender'])); ?></td>
                                <td style="padding: 12px 15px;"><?php echo htmlspecialchars($faculty_member['email']); ?></td>
                                <td style="padding: 12px 15px;"><?php echo htmlspecialchars($faculty_member['department']); ?></td>
                                <td style="padding: 12px 15px;"><?php echo htmlspecialchars($faculty_member['designation']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-top: 20px;">
                <h3 style="color: #856404; margin-bottom: 15px;">⚠️ No Faculty Data Found</h3>
                <p style="color: #856404; margin-bottom: 10px;">No faculty members have been registered yet.</p>
                <p style="color: #856404; font-size: 14px; margin-bottom: 15px;">To add faculty:</p>
                <ol style="color: #856404; font-size: 14px; text-align: left; max-width: 500px; margin: 0 auto;">
                    <li>Use the registration system to register faculty members</li>
                    <li>Make sure to fill in all required faculty information</li>
                    <li>Faculty will appear here once registered</li>
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

<?php
// Determine which section to show
$show_courses = isset($_GET['edit_course']) || isset($_GET['delete_course']) || isset($_GET['courses']) || (isset($_POST['add_course']) || isset($_POST['update_course']) || isset($_POST['assign_faculty']));
$show_student_cards = isset($_GET['program']) && isset($_GET['dept']) && isset($_GET['batch']);
?>

<script>
    const dashboardBtn = document.getElementById('dashboardBtn');
    const studentListBtn = document.getElementById('studentListBtn');
    const facultyListBtn = document.getElementById('facultyListBtn');
    const coursesBtn = document.getElementById('coursesBtn');
    const dashboardSection = document.getElementById('dashboardSection');
    const studentListSection = document.getElementById('studentListSection');
    const facultyListSection = document.getElementById('facultyListSection');
    const coursesSection = document.getElementById('coursesSection');
    const studentCardsSection = document.getElementById('studentCardsSection');

    // Check if student table should be shown
    <?php if ($show_student_cards): ?>
        showStudentCards();
    <?php endif; ?>

    // Check if courses section should be shown
    <?php if ($show_courses): ?>
        showCoursesSection();
    <?php endif; ?>

    function showStudentCards() {
        dashboardSection.style.display = 'none';
        studentListSection.style.display = 'none';
        facultyListSection.style.display = 'none';
        coursesSection.style.display = 'none';
        studentCardsSection.style.display = 'grid';
        studentListBtn.classList.add('active');
        dashboardBtn.classList.remove('active');
        facultyListBtn.classList.remove('active');
        coursesBtn.classList.remove('active');
    }

    function showCoursesSection() {
        dashboardSection.style.display = 'none';
        studentListSection.style.display = 'none';
        facultyListSection.style.display = 'none';
        coursesSection.style.display = 'block';
        studentCardsSection.style.display = 'none';
        coursesBtn.classList.add('active');
        dashboardBtn.classList.remove('active');
        studentListBtn.classList.remove('active');
        facultyListBtn.classList.remove('active');
    }

    function goBack() {
        dashboardSection.style.display = 'none';
        studentListSection.style.display = 'grid';
        facultyListSection.style.display = 'none';
        coursesSection.style.display = 'none';
        studentCardsSection.style.display = 'none';
        studentListBtn.classList.add('active');
        dashboardBtn.classList.remove('active');
        facultyListBtn.classList.remove('active');
        coursesBtn.classList.remove('active');
    }

    dashboardBtn.addEventListener('click', () => {
        dashboardSection.style.display = 'block';
        studentListSection.style.display = 'none';
        facultyListSection.style.display = 'none';
        coursesSection.style.display = 'none';
        studentCardsSection.style.display = 'none';
        dashboardBtn.classList.add('active');
        studentListBtn.classList.remove('active');
        facultyListBtn.classList.remove('active');
        coursesBtn.classList.remove('active');
    });

    studentListBtn.addEventListener('click', () => {
        dashboardSection.style.display = 'none';
        studentListSection.style.display = 'grid';
        facultyListSection.style.display = 'none';
        coursesSection.style.display = 'none';
        studentCardsSection.style.display = 'none';
        studentListBtn.classList.add('active');
        dashboardBtn.classList.remove('active');
        facultyListBtn.classList.remove('active');
        coursesBtn.classList.remove('active');
    });

    facultyListBtn.addEventListener('click', () => {
        dashboardSection.style.display = 'none';
        studentListSection.style.display = 'none';
        facultyListSection.style.display = 'block';
        coursesSection.style.display = 'none';
        studentCardsSection.style.display = 'none';
        facultyListBtn.classList.add('active');
        dashboardBtn.classList.remove('active');
        studentListBtn.classList.remove('active');
        coursesBtn.classList.remove('active');
    });

    coursesBtn.addEventListener('click', () => {
        dashboardSection.style.display = 'none';
        studentListSection.style.display = 'none';
        facultyListSection.style.display = 'none';
        coursesSection.style.display = 'block';
        studentCardsSection.style.display = 'none';
        coursesBtn.classList.add('active');
        dashboardBtn.classList.remove('active');
        studentListBtn.classList.remove('active');
        facultyListBtn.classList.remove('active');
    });

    // Function to handle course selection
    function selectCourse(courseName, courseCode) {
        alert('Selected Course: ' + courseName + (courseCode ? ' (' + courseCode + ')' : '') + '\n\nThis course can be used for future functionality like:\n- Assigning to students\n- Creating exams\n- Managing course content');
    }

    // Function to toggle actions menu
    function toggleActionsMenu(button) {
        const dropdown = button.nextElementSibling;
        const isVisible = dropdown.style.display === 'block';
        
        // Close all other dropdowns first
        document.querySelectorAll('.actions-dropdown').forEach(menu => {
            menu.style.display = 'none';
        });
        
        // Toggle current dropdown
        dropdown.style.display = isVisible ? 'none' : 'block';
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.course-actions')) {
            document.querySelectorAll('.actions-dropdown').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });
</script>

</body>
</html>
