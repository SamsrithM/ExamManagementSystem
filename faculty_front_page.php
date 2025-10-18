<?php
session_start();

// Database connection
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "new_registration_data";
$courses_db_name = "course_registration_data";

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

// Get faculty information from session (you'll need to set this during login)
$faculty_email = $_SESSION['faculty_email'] ?? '';
$faculty_courses = [];

// Get courses assigned to this faculty
if (!empty($faculty_email) && empty($courses_db_error)) {
    $courses_result = $courses_conn->query("SELECT course_id, course_name, course_code, description, assigned_faculty_email, created_at FROM admin_courses WHERE assigned_faculty_email = '$faculty_email' ORDER BY course_name ASC");
    while ($row = $courses_result->fetch_assoc()) {
        $faculty_courses[] = $row;
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
  body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f4f7f9;
    color: #2c3e50;
  }
  /* ===== NAVBAR ===== */
  .navbar {
    background-color: #2c3e50;
    padding: 0 10px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  .navbar a, .dropdown-btn {
    color: white;
    text-decoration: none;
    padding: 0 20px;
    display: inline-flex;
    align-items: center;
    font-weight: 500;
    transition: background 0.3s, color 0.3s;
    height: 56px;
    cursor: pointer;
    border: none;
    background: none;
    font-size: 16px;
    user-select: none;
  }
  .navbar a:hover, .dropdown-btn:hover { background-color: #1abc9c; color: black; }
  .navbar a.active { background-color: #1abc9c; color: black; font-weight: 700; }
  .navbar .spacer { flex-grow: 1; }
  .dropdown-btn img { height: 40px; width: auto; margin-right: 8px; }

  /* ===== MAIN SECTIONS ===== */
  .main { padding: 40px; }
  .section { display: none; }

  /* ===== COURSES GRID ===== */
  #coursesSection {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
  }
  .course-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    cursor: pointer;
    transition: box-shadow 0.3s ease;
    height: 220px;
    width: 100%;
  }
  .course-card:hover { box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
  .course-image { height: 110px; background-size: cover; background-position: center; border-bottom: 1px solid #ddd; }
  .course-content { padding: 12px 14px; flex-grow: 1; display: flex; flex-direction: column; justify-content: center; gap: 6px; }
  .course-title { color: #1a73e8; font-weight: 600; text-decoration: none; cursor: pointer; }
  .course-subtitle { color: #555; font-weight: 400; font-size: 0.85rem; }

  /* ===== CALENDAR ===== */
  .calendar-header { font-size: 24px; font-weight: bold; color: #2c3e50; margin-bottom: 20px; text-align: center; }
  .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; }
  .day-name, .day { padding: 10px; text-align: center; border-radius: 6px; }
  .day-name { background-color: #1abc9c; color: white; font-weight: 600; }
  .day { background-color: #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.1); min-height: 80px; position: relative; cursor: default; }
  .today { background-color: #2ecc71; color: white; font-weight: bold; }
  .event { background-color: #3498db; color: white; padding: 2px 6px; margin-top: 4px; border-radius: 4px; font-size: 12px; cursor: pointer; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
</head>
<body>

<div class="navbar">
  <div class="dropdown">
    <button class="dropdown-btn"><img src="https://www.freeiconspng.com/uploads/address-building-company-home-house-office-real-estate-icon--10.png" alt="Home" /></button>
  </div>
  <a href="#" id="dashboardLink" class="active">🏠 Dashboard</a>
  <a href="#" id="coursesLink">📚 Courses</a>
  <a href="http://localhost/Exam_Management_System/Exam/create_test.php">📝 Create Test</a>
  <a href="view-invigilator-duty.html">⏰ View Invigilator Duty</a>
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
        <div class="course-card" data-course="<?php echo htmlspecialchars($course['course_name']); ?>">
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
  }

  dashboardLink.addEventListener('click', e => { e.preventDefault(); showSection(dashboardSection); });
  coursesLink.addEventListener('click', e => { e.preventDefault(); showSection(coursesSection); });

  showSection(dashboardSection); // default

  // ===== Calendar =====
  const calendarMonthYear = document.getElementById('calendarMonthYear');
  const calendarGrid = document.getElementById('calendarGrid');
  const today = new Date();

  const assignments = [
    { date: '2025-10-13', title: 'JS Assignment', course: 'Web Dev', details: 'Complete exercises.' },
    { date: '2025-10-20', title: 'Calculus Quiz', course: 'Mathematics', details: 'Limits & derivatives.' },
  ];

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
      const match = assignments.filter(a=>a.date===dateStr);
      cell.innerHTML=`<div>${i}</div>`;
      match.forEach(a=>{ const evt=document.createElement('div'); evt.className='event'; evt.textContent=a.title; cell.appendChild(evt); });
      calendarGrid.appendChild(cell);
    }
  }

  renderCalendar(today);

  // ===== Clicking on a course shows calendar =====
  document.querySelectorAll('.course-card').forEach(card=>{
    card.addEventListener('click', ()=>{
      showSection(dashboardSection);
      renderCalendar(today); // refresh calendar if needed
    });
  });
</script>
</body>
</html>