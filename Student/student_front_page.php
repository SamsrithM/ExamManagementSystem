<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['roll_number'])) {
    header("Location: student_login.php");
    exit;
}

// Database connections
$db_host = "localhost";
$db_user = "root";
$db_pass = "";

// --- Connect to student DB ---
$student_db = new mysqli($db_host, $db_user, $db_pass, "new_registration_data");
if ($student_db->connect_error) {
    die("<h2 style='color:red;'>Student DB connection failed: " . $student_db->connect_error . "</h2>");
}

// --- Connect to course registration DB ---
$course_db = new mysqli($db_host, $db_user, $db_pass, "course_registration_data");
if ($course_db->connect_error) {
    die("<h2 style='color:red;'>Course DB connection failed: " . $course_db->connect_error . "</h2>");
}

// --- Connect to test_creation DB ---
$test_db = new mysqli($db_host, $db_user, $db_pass, "test_creation");
if ($test_db->connect_error) {
    die("<h2 style='color:red;'>Test DB connection failed: " . $test_db->connect_error . "</h2>");
}

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

// Fetch assigned courses
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
            if (!empty($row["course$i"])) {
                $assigned_courses[] = $row["course$i"];
            }
        }
    }
    $stmt->close();
}
$course_db->close();

// Fetch upcoming tests
$upcoming_tests = [];
$today_date = date('Y-m-d');
if (!empty($student_program) && !empty($student_branch) && !empty($student_batch)) {
    $stmt = $test_db->prepare("
        SELECT test_id, branch, test_title, test_date, available_from, duration, test_type
        FROM tests
        WHERE branch=? AND test_date >= ?
        ORDER BY test_date ASC
    ");
    $stmt->bind_param("ss", $student_branch, $today_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $upcoming_tests[] = $row;
    }
    $stmt->close();
}
$test_db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Student Dashboard</title>
<style>
body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f9; }
.navbar { background-color: #2c3e50; padding: 0 10px; display: flex; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.navbar a, .dropdown-btn { color: white; text-decoration: none; padding: 0 20px; display: inline-flex; align-items: center; font-weight: 500; transition: background 0.3s, color 0.3s; height: 56px; cursor: pointer; border: none; background: none; font-size: 16px; user-select: none; }
.navbar a:hover, .dropdown-btn:hover { background-color: #1abc9c; color: black; }
.navbar a.active { background-color: #1abc9c; color: black; font-weight: 700; }
.navbar .spacer { flex-grow: 1; }
.dropdown-btn img { height: 40px; width: auto; margin-right: 8px; }
.main-section { padding: 40px; }
#coursesSection { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 40px; padding: 0 40px; }
.course-card { background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; display: flex; flex-direction: column; cursor: pointer; transition: box-shadow 0.3s ease; height: 220px; width: 100%; }
.course-card:hover { box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
.course-image { height: 110px; background-size: cover; background-position: center; border-bottom: 1px solid #ddd; }
.course-content { padding: 12px 14px; flex-grow: 1; display: flex; flex-direction: column; justify-content: center; gap: 6px; }
.course-title { color: #1a73e8; font-weight: 600; text-decoration: none; cursor: pointer; }
.course-subtitle { color: #555; font-weight: 400; font-size: 0.85rem; }
.calendar-header { font-size: 24px; font-weight: bold; color: #2c3e50; margin-bottom: 20px; text-align: center; }
.calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; }
.day-name, .day { padding: 10px; text-align: center; border-radius: 6px; }
.day-name { background-color: #1abc9c; color: white; font-weight: 600; }
.day { background-color: #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.1); min-height: 80px; position: relative; cursor: default; }
.today { background-color: #2ecc71; color: white; font-weight: bold; }
.event { display:block; margin-top:2px; border-radius:4px; padding:2px 4px; font-size:12px; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; cursor:pointer; color:white; background:#3498db; }
#testModal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000; }
#testModalContent { background:white; padding:20px; border-radius:8px; max-width:450px; width:90%; position:relative; box-shadow:0 4px 15px rgba(0,0,0,0.3); }
#closeModal { position:absolute; top:10px; right:15px; cursor:pointer; font-weight:bold; font-size:20px; }
#testModal table { width:100%; border-collapse: collapse; margin-top:10px; }
#testModal td { padding:6px 4px; }
</style>
</head>
<body>
<div class="navbar">
<div class="dropdown">
<button class="dropdown-btn"><img src="https://www.freeiconspng.com/uploads/address-building-company-home-house-office-real-estate-icon--10.png" alt="Home" /></button>
</div>
<a href="#" id="dashboardLink" class="active">🏠 Dashboard</a>
<a href="#" id="coursesLink">📚 My Courses</a>
<a href="view_results.php">📊 Results</a>
<div class="spacer"></div>
<a href="student_view_profile.php">👤 View Profile</a>
<a href="../Ems_start/frontpage.php">🚪 Logout</a>
</div>

<div class="main-section" id="dashboardSection">
<div class="calendar-header" id="calendarMonthYear">Welcome, <?= htmlspecialchars($roll_number) ?></div>
<div class="calendar-grid" id="calendarGrid"></div>
</div>

<!-- Courses Section -->
<div id="coursesSection" class="section">
<?php if (!empty($assigned_courses)): ?>
<?php foreach ($assigned_courses as $course): ?>
<div class="course-card">
<div class="course-image" style="background: linear-gradient(135deg,#1abc9c,#16a085);"></div>
<div class="course-content">
<a href="course_details.php?course_code=<?= urlencode($course) ?>" 
   class="course-link course-title" 
   data-course="<?= htmlspecialchars($course) ?>">
    <?= htmlspecialchars($course) ?>
</a>
<?php echo htmlspecialchars($course); ?>
</a>
</div>
</div>
<?php endforeach; ?>
<?php else: ?>
<div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
<h3 style="color: #856404; margin-bottom: 15px;">📚 No Courses Assigned</h3>
<p style="color: #856404; margin-bottom: 10px;">You don't have any courses assigned yet.</p>
<p style="color: #856404; font-size: 14px;">Contact your administrator to get courses assigned to you.</p>
</div>
<?php endif; ?>
</div>

<!-- Test Modal -->
<div id="testModal">
<div id="testModalContent">
<span id="closeModal">&times;</span>
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

<script>
document.addEventListener('DOMContentLoaded', () => {
const dashboardSection = document.getElementById('dashboardSection');
const coursesSection = document.getElementById('coursesSection');
const dashboardLink = document.getElementById('dashboardLink');
const coursesLink = document.getElementById('coursesLink');

dashboardLink.addEventListener('click', e => {
e.preventDefault();
dashboardSection.style.display = 'block';
coursesSection.style.display = 'none';
dashboardLink.classList.add('active');
coursesLink.classList.remove('active');
});

coursesLink.addEventListener('click', e => {
e.preventDefault();
dashboardSection.style.display = 'none';
coursesSection.style.display = 'grid';
coursesLink.classList.add('active');
dashboardLink.classList.remove('active');
});

const upcomingTests = <?php echo json_encode($upcoming_tests); ?>;

function renderCalendar(date) {
const calendarGrid = document.getElementById('calendarGrid');
calendarGrid.innerHTML = '';
const year = date.getFullYear();
const month = date.getMonth();
const firstDay = new Date(year, month, 1);
const lastDay = new Date(year, month + 1, 0);
const startDay = firstDay.getDay();
const totalDays = lastDay.getDate();
const dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
dayNames.forEach(day => {
const div = document.createElement('div');
div.className = 'day-name';
div.textContent = day;
calendarGrid.appendChild(div);
});
for(let i=0;i<startDay;i++){
const empty = document.createElement('div');
empty.className='day';
calendarGrid.appendChild(empty);
}
const today = new Date();
for(let i=1;i<=totalDays;i++){
const cell = document.createElement('div');
cell.className='day';
if(i===today.getDate() && month===today.getMonth() && year===today.getFullYear()){
cell.classList.add('today');
}
const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(i).padStart(2,'0')}`;
cell.innerHTML = `<div>${i}</div>`;
const dayTests = upcomingTests.filter(t => t.test_date === dateStr);
dayTests.forEach(test=>{
const evt = document.createElement('div');
evt.className='event';
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

document.getElementById('closeModal').addEventListener('click', ()=>{
document.getElementById('testModal').style.display='none';
});
document.getElementById('testModal').addEventListener('click', e=>{
if(e.target.id==='testModal') document.getElementById('testModal').style.display='none';
});

renderCalendar(new Date());

dashboardSection.style.display = 'block';
coursesSection.style.display = 'none';

const courseLinks = document.querySelectorAll('.course-link');
const courseModal = document.getElementById('courseModal');
const courseModalContent = document.getElementById('courseModalContent');
const closeCourseModal = document.getElementById('closeCourseModal');

courseLinks.forEach(link => {
link.addEventListener('click', e => {
e.preventDefault();
const courseCode = link.getAttribute('data-course');
courseModal.style.display = 'flex';
courseModalContent.innerHTML = 'Loading...';
fetch('course_details.php?course_code=' + encodeURIComponent(courseCode))
.then(response => response.text())
.then(html => { courseModalContent.innerHTML = html; })
.catch(err => { courseModalContent.innerHTML = '<p style="color:red;">Failed to load course details.</p>'; });
});
});

closeCourseModal.addEventListener('click', () => { courseModal.style.display = 'none'; });
courseModal.addEventListener('click', e => { if(e.target === courseModal) courseModal.style.display = 'none'; });

});
</script>

<!-- Course Details Modal -->
<div id="courseModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
<div style="background:white; padding:20px; border-radius:8px; max-width:600px; width:90%; max-height:80%; overflow-y:auto; position:relative;">
<span id="closeCourseModal" style="position:absolute; top:10px; right:15px; cursor:pointer; font-weight:bold; font-size:20px;">&times;</span>
<div id="courseModalContent">Loading...</div>
</div>
</div>

</body>
</html>
