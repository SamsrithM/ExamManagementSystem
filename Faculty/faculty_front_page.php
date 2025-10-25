<?php
session_start();

// Database connection
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "new_registration_data";
$courses_db_name = "course_registration_data";
$test_db_name = "test_creation"; // test_creation database

// Connect to main database for faculty info
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    $db_error = "Database connection failed: " . $conn->connect_error;
} else {
    $db_error = '';
}

// Connect to courses database
$courses_conn = new mysqli($db_host, $db_user, $db_pass, $courses_db_name);
if ($courses_conn->connect_error) {
    $courses_db_error = "Courses database connection failed: " . $courses_conn->connect_error;
} else {
    $courses_db_error = '';
}

// Connect to test_creation database
$test_conn = new mysqli($db_host, $db_user, $db_pass, $test_db_name);
if ($test_conn->connect_error) {
    $test_db_error = "Test database connection failed: " . $test_conn->connect_error;
} else {
    $test_db_error = '';
}

// Get faculty info
$faculty_email = $_SESSION['faculty_user'] ?? '';
$faculty_courses = [];
$faculty_tests = [];

// Get courses assigned to this faculty
if (!empty($faculty_email) && empty($courses_db_error)) {
    $courses_result = $courses_conn->query("SELECT course_id, course_name, course_code, description, assigned_faculty_email, created_at FROM admin_courses WHERE assigned_faculty_email = '$faculty_email' ORDER BY course_name ASC");
    while ($row = $courses_result->fetch_assoc()) {
        $faculty_courses[] = $row;
    }
}

// Get upcoming tests created by this faculty
if (!empty($faculty_email) && empty($test_db_error)) {
    $today_date = date('Y-m-d');
    $sql_tests = "SELECT test_id, branch, test_title, test_date, available_from, duration, test_type, created_by
                  FROM tests
                  WHERE created_by = '$faculty_email' 
                    AND test_date >= '$today_date'
                  ORDER BY test_date ASC";
    $tests_result = $test_conn->query($sql_tests);
    if ($tests_result) {
        while ($row = $tests_result->fetch_assoc()) {
            $faculty_tests[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Faculty Dashboard</title>
<style>
  /* Base styles */
  body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f4f7f9;
    color: #2c3e50;
  }

  /* Navbar */
  .navbar {
    background-color: #2c3e50;
    padding: 0 10px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }

  .navbar a,
  .dropdown-btn {
    color: white;
    text-decoration: none;
    padding: 10px 12px;
    display: flex;
    align-items: center;
    font-weight: 500;
    transition: background 0.3s, color 0.3s;
    height: 48px;
    cursor: pointer;
    border: none;
    background: none;
    font-size: 15px;
    user-select: none;
  }

  .navbar a:hover,
  .dropdown-btn:hover {
    background-color: #1abc9c;
    color: black;
  }

  .navbar a.active {
    background-color: #1abc9c;
    color: black;
    font-weight: 700;
  }

  .navbar .spacer {
    flex-grow: 1;
  }

  .dropdown-btn img {
    height: 38px;
    width: auto;
    margin-right: 8px;
  }

  /* Main layout */
  .main {
    padding: 30px 40px;
  }

  .section {
    display: none;
  }

  /* Course Cards */
  #coursesSection {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
  }

  .course-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: box-shadow 0.3s ease, transform 0.2s ease;
    cursor: pointer;
  }

  .course-card:hover {
    box-shadow: 0 8px 18px rgba(0,0,0,0.15);
    transform: translateY(-3px);
  }

  .course-image {
    height: 120px;
    background-size: cover;
    background-position: center;
  }

  .course-content {
    padding: 14px 16px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 6px;
  }

  .course-title {
    color: #1a73e8;
    font-weight: 600;
    text-decoration: none;
    font-size: 1.05rem;
  }

  .course-subtitle {
    color: #555;
    font-size: 0.9rem;
  }

  /* Calendar Section */
  .calendar-header {
    font-size: 24px;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 20px;
    text-align: center;
  }

  .calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 8px;
  }

  .day-name,
  .day {
    padding: 10px;
    text-align: center;
    border-radius: 6px;
  }

  .day-name {
    background-color: #1abc9c;
    color: white;
    font-weight: 600;
  }

  .day {
    background-color: #fff;
    box-shadow: 0 1px 4px rgba(0,0,0,0.1);
    min-height: 80px;
    position: relative;
    cursor: default;
  }

  .today {
    background-color: #2ecc71;
    color: white;
    font-weight: bold;
  }

  .event {
    background-color: #3498db;
    color: white;
    padding: 2px 6px;
    margin-top: 4px;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  /* Modal */
  #testModal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    justify-content: center;
    align-items: center;
    z-index: 1000;
  }

  #testModalContent {
    background: white;
    padding: 20px;
    border-radius: 8px;
    max-width: 450px;
    width: 90%;
    position: relative;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
  }

  #closeModal {
    position: absolute;
    top: 10px;
    right: 15px;
    cursor: pointer;
    font-weight: bold;
    font-size: 20px;
  }

  #testModal h3 {
    margin-bottom: 15px;
  }

  #testModal table {
    width: 100%;
    border-collapse: collapse;
  }

  #testModal table td {
    padding: 6px 8px;
    border-bottom: 1px solid #ddd;
  }

  #testModal table td:first-child {
    font-weight: bold;
    width: 40%;
    color: #555;
  }

  /* Responsive Adjustments */
  @media (max-width: 1024px) {
    .main {
      padding: 20px;
    }
    .calendar-header {
      font-size: 20px;
    }
    .course-card {
      height: auto;
    }
  }

  @media (max-width: 768px) {
    .navbar {
      flex-direction: column;
      align-items: flex-start;
    }

    .navbar a,
    .dropdown-btn {
      width: 100%;
      text-align: left;
      padding: 10px;
    }

    .main {
      padding: 16px;
    }

    .calendar-grid {
      grid-template-columns: repeat(4, 1fr);
      gap: 5px;
    }
  }

  @media (max-width: 480px) {
    .navbar a,
    .dropdown-btn {
      font-size: 14px;
      height: auto;
      padding: 8px 10px;
    }

    .calendar-grid {
      grid-template-columns: repeat(2, 1fr);
    }

    .course-title {
      font-size: 0.95rem;
    }

    .course-subtitle {
      font-size: 0.8rem;
    }

    .main {
      padding: 10px;
    }
  }
</style>

</head>
<body>

<div class="navbar">
  <div class="dropdown">
    <button class="dropdown-btn"><img src="https://www.freeiconspng.com/uploads/address-building-company-home-house-office-real-estate-icon--10.png" alt="Home" /></button>
  </div>
  <a href="#" id="dashboardLink" class="active">🏠 Dashboard</a>
  <a href="#" id="coursesLink">📚 Courses</a>
  <a href="Faculty/choosing_exam_slots.php">✅ Book Slots</a>
  <a href="Exam/create_test.php">📝 Create Test</a>
  <a href="Faculty/view_tests.php">✅ Created Test</a>
  <a href="Faculty/publish_exam.php">📢 Publish Test</a>
  <a href="view_results_faculty.php">📊 View Results</a>
  <a href="view_invigilation_duties.php">⏰ View Invigilator Duty</a>
  <div class="spacer"></div>
  <a href="faculty_view_profile.php">👤 View Profile</a>
  <a href="../Ems_start/frontpage.php">🚪 Logout</a>
</div>

<div class="main">
  <!-- Dashboard Calendar -->
  <div id="dashboardSection" class="section">
    <div class="calendar-header" id="calendarMonthYear"></div>
    <div class="calendar-grid" id="calendarGrid"></div>
  </div>

  <!-- Courses Section -->
  <div id="coursesSection" class="section">
    <?php if (!empty($faculty_courses)): ?>
      <?php foreach ($faculty_courses as $course): ?>
          <div class="course-card" data-course="<?php echo htmlspecialchars($course['course_code']); ?>">
          <div class="course-image" style="background: linear-gradient(135deg,#1abc9c,#16a085);"></div>
          <div class="course-content">
            <a class="course-title"><?php echo htmlspecialchars($course['course_name']); ?></a>
            <div class="course-subtitle">
              <?php if (!empty($course['course_code'])): ?>
                Code: <?php echo htmlspecialchars($course['course_code']); ?>
              <?php endif; ?>
            </div>
            <?php if (!empty($course['description'])): ?>
              <div class="course-description" style="font-size: 0.8rem; color: #666; margin-top: 4px;">
                <?php echo htmlspecialchars(substr($course['description'], 0, 100)) . (strlen($course['description']) > 100 ? '...' : ''); ?>
              </div>
            <?php endif; ?>
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
const dashboardLink = document.getElementById('dashboardLink');
const coursesLink = document.getElementById('coursesLink');
const dashboardSection = document.getElementById('dashboardSection');
const coursesSection = document.getElementById('coursesSection');

function showSection(section) {
  dashboardSection.style.display = 'none';
  coursesSection.style.display = 'none';
  section.style.display = section === dashboardSection ? 'block' : 'grid';
  dashboardLink.classList.remove('active');
  coursesLink.classList.remove('active');
  (section === dashboardSection ? dashboardLink : coursesLink).classList.add('active');

  if(section === coursesSection){
    document.querySelectorAll('.course-card').forEach(card=>{
      card.addEventListener('click', ()=>{
        const courseCode = card.getAttribute('data-course'); 
        window.location.href = `faculty_course_tests.php?course_code=${encodeURIComponent(courseCode)}`;
      });
    });
  }
}
dashboardLink.addEventListener('click', e => { e.preventDefault(); showSection(dashboardSection); });
coursesLink.addEventListener('click', e => { e.preventDefault(); showSection(coursesSection); });

showSection(dashboardSection);

const calendarMonthYear = document.getElementById('calendarMonthYear');
const calendarGrid = document.getElementById('calendarGrid');
const today = new Date();

const assignments = <?php echo json_encode($faculty_tests); ?>;

function renderCalendar(date) {
  calendarGrid.innerHTML = '';
  const year = date.getFullYear();
  const month = date.getMonth();
  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const startDay = firstDay.getDay();
  const totalDays = lastDay.getDate();

  calendarMonthYear.textContent = firstDay.toLocaleString('default', { month: 'long', year: 'numeric' });

  const dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
  dayNames.forEach(day => {
    const div = document.createElement('div'); div.className = 'day-name'; div.textContent = day;
    calendarGrid.appendChild(div);
  });

  for (let i=0;i<startDay;i++){ const div=document.createElement('div'); div.className='day'; calendarGrid.appendChild(div); }
  for (let i=1;i<=totalDays;i++){
    const cell=document.createElement('div'); cell.className='day';
    if(i===today.getDate() && month===today.getMonth() && year===today.getFullYear()){cell.classList.add('today');}
    const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(i).padStart(2,'0')}`;
    const match = assignments.filter(a=>a.test_date===dateStr);
    cell.innerHTML=`<div>${i}</div>`;
    match.forEach(a=>{
      const evt=document.createElement('div');
      evt.className='event';
      evt.textContent=a.test_title + ' (' + a.branch + ')';

      evt.addEventListener('click', (e)=>{
        e.stopPropagation();
        document.getElementById('modalTestTitle').textContent = a.test_title;
        document.getElementById('modalBranch').textContent = a.branch;
        document.getElementById('modalDate').textContent = a.test_date;
        document.getElementById('modalAvailable').textContent = a.available_from;
        document.getElementById('modalDuration').textContent = a.duration;
        document.getElementById('modalType').textContent = a.test_type;
        document.getElementById('testModal').style.display = 'flex';
      });

      cell.appendChild(evt);
    });
    calendarGrid.appendChild(cell);
  }
}

renderCalendar(today);

document.querySelectorAll('.course-card').forEach(card=>{
  card.addEventListener('click', ()=>{
    const courseCode = card.getAttribute('data-course'); 
    window.location.href = `faculty_course_tests.php?course_code=${encodeURIComponent(courseCode)}`;
  });
});

// Modal Close
document.getElementById('closeModal').addEventListener('click', ()=>{
  document.getElementById('testModal').style.display = 'none';
});
document.getElementById('testModal').addEventListener('click', (e)=>{
  if(e.target.id==='testModal') document.getElementById('testModal').style.display = 'none';
});
</script>
</body>
</html>
