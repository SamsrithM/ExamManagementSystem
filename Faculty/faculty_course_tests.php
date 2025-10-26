<?php
session_start();
if (!isset($_SESSION['faculty_user'])) {
    header("Location: faculty_login.php");
    exit;
}

$faculty_mail = $_SESSION['faculty_user'];
$course_code = $_GET['course_code'] ?? '';

$db_host = getenv('DB_HOST') ?: 'mysql';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_ROOM') ?: 'room_allocation';

if (empty($course_code)) {
    echo "<p style='color:red;'>Invalid course selected.</p>";
    exit;
}

// Database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch exams published by this faculty for this course
$sql = "SELECT t.test_id, t.test_title, t.test_date, t.available_from, t.duration, t.test_type, p.course_code
        FROM tests t
        INNER JOIN published_exam p ON t.test_id = p.test_id
        WHERE p.course_code = ? AND p.faculty_mail = ?
        ORDER BY t.test_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $course_code, $faculty_mail);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Exams for <?php echo htmlspecialchars($course_code); ?></title>
<style>
body { font-family: Arial, sans-serif; background:#f4f7f9; margin:0; padding:20px;}
h2 { text-align:center; color:#2c3e50; font-size:24px; margin-bottom:20px;}
table { width:100%; border-collapse:collapse; margin-top:20px; background:white; box-shadow:0 4px 10px rgba(0,0,0,0.05); border-radius:8px; overflow:hidden;}
th, td { border:1px solid #ccc; padding:12px; text-align:left; font-size:15px;}
th { background-color:#1abc9c; color:white; font-size:14px; text-transform:uppercase;}
tr:nth-child(even) { background:#f2f2f2;}
.no-exams { padding:30px; background:#fff3cd; color:#856404; text-align:center; border-radius:8px; margin-top:20px; font-size:16px; font-weight:600;}
.back-link { display:inline-block; background:#28a745; color:white; padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:bold; box-shadow:0 4px 8px rgba(0,0,0,0.2); transition:0.3s;}
.back-link:hover { background:#218838; }
@media screen and (max-width:480px){
  table, thead, tbody, th, td, tr { display:block; }
  thead { display:none; }
  tr { margin-bottom:15px; border:1px solid #ccc; border-radius:8px; padding:10px; background:#fff; }
  td { text-align:left; padding:8px 10px; position:relative; }
  td::before { content: attr(data-label); font-weight:bold; color:#1abc9c; display:block; margin-bottom:5px; }
  .back-link { display:block; width:100%; text-align:center; margin-top:20px; }
}
</style>
</head>
<body>
<h2>Exams for Course: <?php echo htmlspecialchars($course_code); ?></h2>

<?php if($result->num_rows > 0): ?>
<table>
<tr>
  <th>Test Title</th>
  <th>Test Date</th>
  <th>Available From</th>
  <th>Duration</th>
  <th>Test Type</th>
  <th>Course Code</th>
</tr>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
  <td data-label="Test Title"><?php echo htmlspecialchars($row['test_title']); ?></td>
  <td data-label="Test Date"><?php echo htmlspecialchars($row['test_date']); ?></td>
  <td data-label="Available From"><?php echo htmlspecialchars($row['available_from']); ?></td>
  <td data-label="Duration"><?php echo htmlspecialchars($row['duration']); ?></td>
  <td data-label="Test Type"><?php echo htmlspecialchars($row['test_type']); ?></td>
  <td data-label="Course Code"><?php echo htmlspecialchars($row['course_code']); ?></td>
</tr>
<?php endwhile; ?>
</table>
<?php else: ?>
<div class="no-exams">No exams published for this course by you.</div>
<?php endif; ?>

<div style="text-align:center; margin-top:30px;">
<a href="faculty_front_page.php" class="back-link">&#8592; Back to Dashboard</a>
</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
