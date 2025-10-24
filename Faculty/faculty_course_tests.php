<?php
session_start();
if (!isset($_SESSION['faculty_user'])) {
    header("Location: faculty_login.php");
    exit;
}

$faculty_mail = $_SESSION['faculty_user'];
$course_code = $_GET['course_code'] ?? '';

if (empty($course_code)) {
    echo "<p style='color:red;'>Invalid course selected.</p>";
    exit;
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "test_creation"; // tests & published_exam tables

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Correct query: only tests published by this faculty for this course
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
<title>Course Exams</title>
<style>
body { font-family: Arial, sans-serif; background:#f4f7f9; padding:20px; }
h2 { color:#2c3e50; }
table { border-collapse: collapse; width: 100%; margin-top: 20px; }
th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
th { background-color: #1abc9c; color: white; }
tr:nth-child(even) { background-color: #f2f2f2; }
.no-exams { padding: 30px; background-color: #fff3cd; color: #856404; text-align: center; border-radius: 8px; margin-top: 20px; }
</style>
</head>
<body>
<h2>Exams for Course: <?php echo htmlspecialchars($course_code); ?></h2>

<?php if ($result->num_rows > 0): ?>
<table>
    <tr>
        <th>Test Title</th>
        <th>Test Date</th>
        <th>Available From</th>
        <th>Duration</th>
        <th>Test Type</th>
        <th>Course Code</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['test_title']); ?></td>
        <td><?php echo htmlspecialchars($row['test_date']); ?></td>
        <td><?php echo htmlspecialchars($row['available_from']); ?></td>
        <td><?php echo htmlspecialchars($row['duration']); ?></td>
        <td><?php echo htmlspecialchars($row['test_type']); ?></td>
        <td><?php echo htmlspecialchars($row['course_code']); ?></td>
    </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
<div class="no-exams">No exams published for this course by you.</div>
<?php endif; ?>
<div style="margin-top:30px; text-align:center;">
    <a href="faculty_front_page.php" style="
        display:inline-block;
        background-color:#28a745;
        color:white;
        padding:12px 24px;
        border-radius:8px;
        text-decoration:none;
        font-weight:bold;
        box-shadow:0 4px 8px rgba(0,0,0,0.2);
        transition:0.3s;
    " onmouseover="this.style.backgroundColor='#218838'" onmouseout="this.style.backgroundColor='#28a745'">
        &#8592; Back to Dashboard
    </a>
</div>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
