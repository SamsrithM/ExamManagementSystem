<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['roll_number'])) {
    header("Location: student_login.php");
    exit;
}

// DB connections
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
    
// Student DB
$student_db = new mysqli($db_host, $db_user, $db_pass, "new_registration_data");
if ($student_db->connect_error) die("Student DB connection failed: " . $student_db->connect_error);

// Course DB
$course_db = new mysqli($db_host, $db_user, $db_pass, "course_registration_data");
if ($course_db->connect_error) die("Course DB connection failed: " . $course_db->connect_error);

// Test DB
$test_db = new mysqli($db_host, $db_user, $db_pass, "test_creation");
if ($test_db->connect_error) die("Test DB connection failed: " . $test_db->connect_error);

// Get student info
$roll_number = $_SESSION['roll_number'];
$stmt = $student_db->prepare("SELECT course, department, batch FROM students_new_data WHERE roll_number=?");
$stmt->bind_param("s", $roll_number);
$stmt->execute();
$result = $stmt->get_result();
$student_program = $student_branch = $student_batch = '';
if ($row = $result->fetch_assoc()) {
    $student_program = $row['course'];
    $student_branch = $row['department'];
    $student_batch = $row['batch'];
}
$stmt->close();
$student_db->close();

// Assigned courses
$assigned_courses = [];
if (!empty($student_program) && !empty($student_branch) && !empty($student_batch)) {
    $stmt = $course_db->prepare("
        SELECT course1, course2, course3, course4
        FROM assign_courses_students
        WHERE LOWER(program)=LOWER(?) AND LOWER(branch)=LOWER(?) AND batch_year=?
    ");
    $stmt->bind_param("sss", $student_program, $student_branch, $student_batch);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        for ($i = 1; $i <= 4; $i++) {
            if (!empty($row["course$i"])) $assigned_courses[] = $row["course$i"];
        }
    }
    $stmt->close();
}
$course_db->close();

// Upcoming tests
$upcoming_tests = [];
$today_date = date('Y-m-d');
if (!empty($student_branch)) {
    $stmt = $test_db->prepare("
        SELECT test_id, branch, test_title, test_date, available_from, duration, test_type
        FROM tests
        WHERE branch=? AND test_date >= ?
        ORDER BY test_date ASC
    ");
    $stmt->bind_param("ss", $student_branch, $today_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $upcoming_tests[] = $row;
    $stmt->close();
}
$test_db->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard</title>
<style>
/* ===== Global Styles ===== */
body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f4f7f9; }
a { text-decoration:none; color: inherit; }

/* ===== Navbar ===== */
.navbar { display:flex; align-items:center; background:#2c3e50; padding:0 15px; height:56px; box-shadow:0 2px 4px rgba(0,0,0,0.1);}
.navbar a, .dropdown-btn { color:white; padding:0 15px; font-weight:500; display:inline-flex; align-items:center; cursor:pointer; transition:0.3s;}
.navbar a.active, .navbar a:hover, .dropdown-btn:hover { background:#1abc9c; color:black; font-weight:700; }
.navbar .spacer { flex-grow:1; }
.dropdown-btn img { height:40px; margin-right:8px; }

/* ===== Main Section ===== */
.main-section { padding:20px 20px 40px 20px; }

/* ===== Calendar ===== */
.calendar-header { font-size:24px; font-weight:bold; color:#2c3e50; text-align:center; margin-bottom:20px; }
.calendar-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:8px; }
.day-name, .day { padding:10px; text-align:center; border-radius:6px; }
.day-name { background:#1abc9c; color:white; font-weight:600; }
.day { background:#fff; box-shadow:0 1px 4px rgba(0,0,0,0.1); min-height:80px; position:relative; }
.today { background:#2ecc71; color:white; font-weight:bold; }
.event { display:block; margin-top:2px; border-radius:4px; padding:2px 4px; font-size:12px; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; cursor:pointer; color:white; background:#3498db; transition:0.2s; }
.event:hover { background:#2980b9; }

/* ===== Courses ===== */
#coursesSection { display:grid; grid-template-columns:repeat(auto-fill, minmax(280px,1fr)); gap:20px; margin-top:20px; }
.course-card { background:#fff; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1); cursor:pointer; transition:0.3s; overflow:hidden; height:220px; display:flex; flex-direction:column; }
.course-card:hover { box-shadow:0 8px 20px rgba(0,0,0,0.15);}
.course-image { height:110px; background:linear-gradient(135deg,#1abc9c,#16a085); }
.course-content { padding:12px; flex-grow:1; display:flex; flex-direction:column; justify-content:center; gap:6px; }
.course-title { color:#1a73e8; font-weight:600; cursor:pointer; }
.course-subtitle { color:#555; font-size:0.85rem; }

/* ===== Modals ===== */
.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000; }
.modal-content { background:#fff; padding:20px; border-radius:8px; max-width:600px; width:90%; max-height:80%; overflow-y:auto; position:relative; box-shadow:0 4px 15px rgba(0,0,0,0.3);}
.modal-close { position:absolute; top:10px; right:15px; cursor:pointer; font-weight:bold; font-size:20px; }
#testModal table { width:100%; border-collapse: collapse; margin-top:10px; }
#testModal td { padding:6px 4px; }

/* ===== Responsive ===== */
@media(max-width:600px){
    .navbar a, .dropdown-btn { padding:0 8px; font-size:14px; }
    .calendar-grid .day, .calendar-grid .day-name { font-size:12px; padding:6px; }
    .course-card { height:200px; }
}
</style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div class="dropdown"><button class="dropdown-btn"><img src="https://www.freeiconspng.com/uploads/address-building-company-home-house-office-real-estate-icon--10.png" alt="Home" /></button></div>
    <a href="#" id="dashboardLink" class="active">🏠 Dashboard</a>
    <a href="#" id="coursesLink">📚 My Courses</a>
    <a href="view_results.php">📊 Results</a>
    <div class="spacer"></div>
    <a href="student_view_profile.php">👤 View Profile</a>
    <a href="../Ems_start/frontpage.php">🚪 Logout</a>
</div>

<!-- Dashboard -->
<div class="main-section" id="dashboardSection">
    <div class="calendar-header" id="calendarMonthYear">Welcome, <?= htmlspecialchars($roll_number) ?></div>
    <div class="calendar-grid" id="calendarGrid"></div>
</div>

<!-- Courses Section -->
<div id="coursesSection" class="main-section">
<?php if(!empty($assigned_courses)): ?>
<?php foreach($assigned_courses as $course): ?>
<div class="course-card">
    <div class="course-image"></div>
    <div class="course-content">
        <a href="course_details.php?course_code=<?= urlencode($course) ?>" 
           class="course-link course-title" 
           data-course="<?= htmlspecialchars($course) ?>">
           <?= htmlspecialchars($course) ?>
        </a>
    </div>
</div>
<?php endforeach; ?>
<?php else: ?>
<div style="grid-column:1/-1; text-align:center; padding:40px; background:#fff3cd; border:1px solid #ffeaa7; border-radius:12px;">
<h3 style="color:#856404;">📚 No Courses Assigned</h3>
<p style="color:#856404;">Contact your administrator to get courses assigned.</p>
</div>
<?php endif; ?>
</div>

<!-- Test Modal -->
<div id="testModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" id="closeModal">&times;</span>
        <h3 id="modalTestTitle"></h3>
        <table>
            <tr><td>Branch</td><td id="modalBranch"></td></tr>
            <tr><td>Test Date</td><td id="modalDate"></td></tr>
            <tr><td>Available From</td><td id="modalAvailable"></td></tr>
            <tr><td>Duration</td><td id="modalDuration"></td></tr>
            <tr><td>Test Type</td><td id="modalType"></td></tr>
        </table>
    </div>
</div>

<!-- Course Modal -->
<div id="courseModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" id="closeCourseModal">&times;</span>
        <div id="courseModalContent">Loading...</div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

const dashboardSection = document.getElementById('dashboardSection');
const coursesSection = document.getElementById('coursesSection');
const dashboardLink = document.getElementById('dashboardLink');
const coursesLink = document.getElementById('coursesLink');

dashboardLink.addEventListener('click', e => {
    e.preventDefault();
    dashboardSection.style.display='block';
    coursesSection.style.display='none';
    dashboardLink.classList.add('active');
    coursesLink.classList.remove('active');
});

coursesLink.addEventListener('click', e => {
    e.preventDefault();
    dashboardSection.style.display='none';
    coursesSection.style.display='grid';
    coursesLink.classList.add('active');
    dashboardLink.classList.remove('active');
});

// Upcoming tests from PHP
const upcomingTests = <?= json_encode($upcoming_tests); ?>;

// Calendar
function renderCalendar(date){
    const calendarGrid = document.getElementById('calendarGrid');
    calendarGrid.innerHTML='';
    const year = date.getFullYear();
    const month = date.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month+1, 0);
    const startDay = firstDay.getDay();
    const totalDays = lastDay.getDate();
    const dayNames=['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

    dayNames.forEach(d => {
        const div = document.createElement('div');
        div.className='day-name';
        div.textContent=d;
        calendarGrid.appendChild(div);
    });

    for(let i=0;i<startDay;i++){ const empty=document.createElement('div'); empty.className='day'; calendarGrid.appendChild(empty); }

    const today = new Date();
    for(let i=1;i<=totalDays;i++){
        const cell = document.createElement('div'); cell.className='day';
        if(i===today.getDate() && month===today.getMonth() && year===today.getFullYear()) cell.classList.add('today');
        const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(i).padStart(2,'0')}`;
        cell.innerHTML=`<div>${i}</div>`;
        const dayTests = upcomingTests.filter(t=>t.test_date===dateStr);
        dayTests.forEach(test=>{
            const evt=document.createElement('div'); evt.className='event';
            evt.textContent = test.test_title + " (" + test.branch + ")";
            evt.addEventListener('click', e=>{
                e.stopPropagation();
                document.getElementById('modalTestTitle').textContent = test.test_title;
                document.getElementById('modalBranch').textContent = test.branch;
                document.getElementById('modalDate').textContent = test.test_date;
                document.getElementById('modalAvailable').textContent = test.available_from;
                document.getElementById('modalDuration').textContent = test.duration;
                document.getElementById('modalType').textContent = test.test_type;
                document.getElementById('testModal').style.display='flex';
            });
            cell.appendChild(evt);
        });
        calendarGrid.appendChild(cell);
    }
    document.getElementById('calendarMonthYear').textContent = date.toLocaleString('default',{month:'long', year:'numeric'});
}

document.getElementById('closeModal').addEventListener('click', ()=>{ document.getElementById('testModal').style.display='none'; });
document.getElementById('testModal').addEventListener('click', e=>{ if(e.target.id==='testModal') document.getElementById('testModal').style.display='none'; });

renderCalendar(new Date());

// Course modal
const courseLinks=document.querySelectorAll('.course-link');
const courseModal=document.getElementById('courseModal');
const courseModalContent=document.getElementById('courseModalContent');
const closeCourseModal=document.getElementById('closeCourseModal');

courseLinks.forEach(link=>{
    link.addEventListener('click', e=>{
        e.preventDefault();
        const courseCode=link.getAttribute('data-course');
        courseModal.style.display='flex';
        courseModalContent.innerHTML='Loading...';
        fetch('course_details.php?course_code=' + encodeURIComponent(courseCode))
        .then(res=>res.text())
        .then(html=>{ courseModalContent.innerHTML=html; })
        .catch(err=>{ courseModalContent.innerHTML='<p style="color:red;">Failed to load course details.</p>'; });
    });
});

closeCourseModal.addEventListener('click', ()=>{ courseModal.style.display='none'; });
courseModal.addEventListener('click', e=>{ if(e.target===courseModal) courseModal.style.display='none'; });

dashboardSection.style.display='block';
coursesSection.style.display='none';

});
</script>

</body>
</html>
