<?php
session_start();

if (!isset($_SESSION['admin_user'])) {
    header("Location: admin_login.php");
    exit;
}

$programs = ['btech', 'mtech', 'phd'];
$departments = ['AIDS', 'CSE', 'ECE', 'MECH'];
$batch_years = [2021, 2022, 2023, 2024, 2025];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<style>
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
    .sidebar h2 { margin-bottom: 30px; font-size: 22px; }
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
    .sidebar a:hover, .sidebar a.active { background-color: #0059b3; }
    .main { margin-left: 230px; padding: 40px; width: 100%; }

    h1 { color: #003366; margin-bottom: 20px; }

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

    /* Student List UI */
    .student-list-panel { display: flex; gap: 40px; margin-top: 40px; }
    .program-block {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.11);
        min-width: 300px;
        flex: 1;
    }
    .program-block h2 {
        text-align: center;
        color: #003366;
        margin-bottom: 25px;
    }
    .dept-section { margin-bottom: 35px; }
    .dept-title {
        font-weight: bold;
        color: #0066cc;
        margin-bottom: 12px;
        text-align: center;
    }
    .batch-btns {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        justify-content: center;
        margin-bottom: 8px;
    }
    .batch-btn {
        background: #4da3ff;
        color: white;
        border: none;
        border-radius: 7px;
        padding: 10px 22px;
        font-size: 16px;
        cursor: pointer;
        margin-bottom: 6px;
        transition: transform 0.2s;
        text-decoration:none;
    }
    .batch-btn:hover {
        background: #007acc;
        transform: scale(1.05);
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
    .logout:hover { background-color: darkred; }
</style>
</head>
<body>
<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="#" id="dashboardBtn" class="active">🏠 Dashboard</a>
    <a href="#" id="studentListBtn">👥 Student List</a>
    <a href="#" id="facultyListBtn">👨‍🏫 Faculty List</a>
    <a href="#" id="coursesBtn">📚 Courses</a>
    <a href="http://localhost/Exam_Management_System/Invigilation_duty/view_invigilation_duties.php">📝 View Invigilation Duties</a>
    <a href="admin_upload_photo.php">👤 View Profile</a>
    <a href="http://localhost/Exam_Management_System/Ems_start/frontpage.php" class="logout">🚪 Logout</a>
</div>
<div class="main">
    <div id="dashboardSection">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['admin_user']); ?></h1>
        <div class="dashboard-btns">
            <button class="move-btn" onclick="window.location.href='http://localhost/Exam_Management_System/Invigilation_duty/assign_duties.php'">🎓 Assign Invigilator Duties</button>
            <button class="move-btn" onclick="window.location.href='http://localhost/Exam_Management_System/Seating_arragement/seating_arrangement.php'">🪑 Seating Arrangement</button>
        </div>
    </div>
    <div id="studentListSection" style="display: none;">
        <h1>Student List</h1>
        <div class="student-list-panel">
            <?php foreach ($programs as $prog): ?>
            <div class="program-block">
                <h2><?php echo htmlspecialchars($prog); ?></h2>
                <?php foreach ($departments as $dept): ?>
                <div class="dept-section">
                    <div class="dept-title"><?php echo htmlspecialchars($dept); ?></div>
                    <div class="batch-btns">
                        <?php foreach ($batch_years as $batch): ?>
                            <a class="batch-btn" href="student_list.php?course=<?php echo urlencode($prog); ?>&dept=<?php echo urlencode($dept); ?>&batch=<?php echo urlencode($batch); ?>"><?php echo htmlspecialchars($batch); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div id="facultyListSection" style="display: none;">
        <h1>Faculty List</h1>
        <p>Loading faculty data...</p>
        <script>
            fetch('faculty_list.php').then(response => response.text()).then(data => {
                document.getElementById('facultyListSection').innerHTML = data;
            });
        </script>
    </div>
    <div id="coursesSection" style="display: none;">
        <h1>Courses Management</h1>
        <p>Loading courses data...</p>
        <script>
            fetch('courses_handler.php').then(response => response.text()).then(data => {
                document.getElementById('coursesSection').innerHTML = data;
            });
        </script>
    </div>
</div>
<script>
document.getElementById('dashboardBtn').addEventListener('click', function() {
    showSection('dashboardSection');
    setActiveButton('dashboardBtn');
});
document.getElementById('studentListBtn').addEventListener('click', function() {
    showSection('studentListSection');
    setActiveButton('studentListBtn');
});
document.getElementById('facultyListBtn').addEventListener('click', function() {
    showSection('facultyListSection');
    setActiveButton('facultyListBtn');
});
document.getElementById('coursesBtn').addEventListener('click', function() {
    showSection('coursesSection');
    setActiveButton('coursesBtn');
});
function showSection(sectionId) {
    const sections = ['dashboardSection', 'studentListSection', 'facultyListSection', 'coursesSection'];
    sections.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.style.display = id === sectionId ? 'block' : 'none';
        }
    });
}
function setActiveButton(buttonId) {
    document.querySelectorAll('.sidebar a').forEach(btn => btn.classList.remove('active'));
    document.getElementById(buttonId).classList.add('active');
}
// Default to showing dashboard first!
showSection('dashboardSection');
setActiveButton('dashboardBtn');
</script>
</body>
</html>
