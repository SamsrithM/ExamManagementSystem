<?php
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['admin_user'])) {
    header("Location: admin_login.php");
    exit;
}

// Database connection using environment variables
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'new_registration_data';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("<h2 style='color:red;'>Connection failed: " . $conn->connect_error . "</h2>");
}

// Programs, Departments and Batches
$programs = [
    'BTech' => ['CSE', 'ECE', 'MECH', 'AIDS'],
    'MTech' => ['CSE', 'ECE', 'MECH', 'AIDS'],
    'PhD'   => ['CSE', 'ECE', 'MECH', 'AIDS']
];
$batches = ['2022','2023','2024','2025'];

// Get selected filters
$selected_program = $_GET['program'] ?? '';
$selected_department = $_GET['dept'] ?? '';
$selected_batch = $_GET['batch'] ?? '';

// Fetch students if all filters are selected
$students = [];
if ($selected_program && $selected_department && $selected_batch) {
    $stmt = $conn->prepare("
        SELECT student_id, first_name, last_name, gender, dob, batch, department, roll_number, institute_email, course, semester
        FROM students_new_data 
        WHERE course = ? AND department = ? AND batch = ? 
        ORDER BY roll_number ASC
    ");
    $stmt->bind_param("ssi", $selected_program, $selected_department, $selected_batch);
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
<title>Students by Batch</title>
<style>
  body {
    font-family: 'Segoe UI', sans-serif;
    background: #f5f8fc;
    margin: 0;
    padding: 0;
  }

  .container {
    width: 90%;
    max-width: 1200px;
    margin: 20px auto;
  }

  h2 {
    color: #003366;
    margin-bottom: 20px;
    font-size: 26px;
  }

  .program-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
  }

  .program-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: 0.3s;
  }

  .program-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
  }

  .program-name {
    font-size: 18px;
    font-weight: bold;
    color: #0066cc;
    margin-bottom: 10px;
  }

  .department-name {
    font-weight: 600;
    margin-bottom: 5px;
    color: #003366;
  }

  .batch-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
  }

  .batch-list a {
    padding: 6px 12px;
    background: #0066cc;
    color: white;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
  }

  .batch-list a:hover {
    background: #004c99;
  }

  .back-btn {
    background: #0066cc;
    color: white;
    padding: 10px 20px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    margin: 20px 0;
    text-decoration: none;
    display: inline-block;
    transition: 0.3s;
  }

  .back-btn:hover {
    background: #004c99;
  }

  .student-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  .student-table th,
  .student-table td {
    padding: 12px;
    text-align: left;
    font-size: 14px;
  }

  .student-table th {
    background: #003366;
    color: white;
  }

  .student-table tr:nth-child(even) {
    background: #f2f6fa;
  }

  .no-students {
    text-align: center;
    padding: 40px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    color: #666;
  }

  .search-box {
    margin-top: 20px;
    margin-bottom: 10px;
    display: flex;
    justify-content: flex-end;
  }

  .search-box input {
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid #ccc;
    width: 250px;
  }

  /* Responsive Styles */
  @media (max-width: 768px) {
    h2 {
      font-size: 22px;
    }

    .program-section {
      grid-template-columns: 1fr;
    }

    .search-box {
      justify-content: center;
    }

    .student-table,
    thead,
    tbody,
    th,
    td,
    tr {
      display: block;
    }

    thead {
      display: none;
    }

    .student-table tr {
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 8px;
      padding: 10px;
      background: #fff;
    }

    .student-table td {
      text-align: left;
      padding: 8px 10px;
      position: relative;
    }

    .student-table td::before {
      content: attr(data-label);
      font-weight: bold;
      color: #003366;
      display: block;
      margin-bottom: 5px;
    }
  }

  @media (max-width: 480px) {
    .search-box input {
      width: 100%;
    }

    .back-btn {
      width: 100%;
      text-align: center;
    }

    .student-table td {
      font-size: 13px;
    }
  }
</style>
<script>
function goBack() { window.history.back(); }

// Search functionality
function searchStudents() {
    const input = document.getElementById('studentSearch').value.toLowerCase();
    const table = document.getElementById('studentsTable');
    const rows = table.getElementsByTagName('tr');

    for(let i=1; i<rows.length; i++){ // skip header
        const cells = rows[i].getElementsByTagName('td');
        let match = false;
        for(let j=1; j<=3; j++){ // check Name, Roll Number, Email
            if(cells[j].innerText.toLowerCase().includes(input)){
                match = true;
                break;
            }
        }
        rows[i].style.display = match ? '' : 'none';
    }
}
</script>
</head>
<body>
<div class="container">

    <h2>Programs & Batches</h2>
    <div class="program-section">
        <?php foreach($programs as $program => $departments): ?>
            <div class="program-card">
                <div class="program-name"><?= htmlspecialchars($program) ?></div>
                <?php foreach($departments as $dept): ?>
                    <div class="department-name"><?= htmlspecialchars($dept) ?></div>
                    <div class="batch-list">
                        <?php foreach($batches as $batch): ?>
                            <a href="?program=<?= urlencode($program) ?>&dept=<?= urlencode($dept) ?>&batch=<?= $batch ?>"><?= htmlspecialchars($batch) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if($selected_program && $selected_department && $selected_batch): ?>
        <button class="back-btn" onclick="goBack()">← Back to Program List</button>

        <?php if(!empty($students)): ?>
            <h2><?= htmlspecialchars($selected_program) ?> - <?= htmlspecialchars($selected_department) ?> - Batch <?= htmlspecialchars($selected_batch) ?></h2>
            <p>Total Students: <?= count($students) ?></p>

            <div class="search-box">
                <input type="text" id="studentSearch" onkeyup="searchStudents()" placeholder="🔍 Search by Name, Roll Number or Email">
            </div>

            <div style="overflow-x:auto;">
                <table class="student-table" id="studentsTable">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Name</th>
                            <th>Roll Number</th>
                            <th>Email</th>
                            <th>Program</th>
                            <th>Semester</th>
                            <th>Gender</th>
                            <th>DOB</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($students as $index => $student): ?>
                            <tr>
                                <td><?= $index+1 ?></td>
                                <td><?= htmlspecialchars($student['first_name'].' '.$student['last_name']) ?></td>
                                <td><?= htmlspecialchars($student['roll_number']) ?></td>
                                <td><?= htmlspecialchars($student['institute_email']) ?></td>
                                <td><?= htmlspecialchars($student['course']) ?></td>
                                <td><?= htmlspecialchars($student['semester']) ?></td>
                                <td><?= ucfirst(htmlspecialchars($student['gender'])) ?></td>
                                <td><?= htmlspecialchars($student['dob']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <div class="no-students">
                <h3>No Students Available</h3>
                <p>No students found for <?= htmlspecialchars($selected_program) ?> - <?= htmlspecialchars($selected_department) ?> - Batch <?= htmlspecialchars($selected_batch) ?></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Return to Dashboard at the bottom -->
    <a href="admin_front_page.php" class="back-btn">← Return to Dashboard</a>

</div>
</body>
</html>
            