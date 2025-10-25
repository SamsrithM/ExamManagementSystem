<?php
session_start();

if (!isset($_SESSION['faculty_user'])) {
    header("Location: faculty_login.php");
    exit;
}

$faculty_email = $_SESSION['faculty_user'];

// DB connection
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "test_creation";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch exams created by this faculty
$stmt = $conn->prepare("
    SELECT m.roll_number, m.test_id, m.marks_obtained, m.total_marks, m.submitted_at, t.test_title, t.test_date
    FROM marks_awarded m
    JOIN tests t ON m.test_id = t.test_id
    WHERE t.created_by = ?
    ORDER BY t.test_date DESC, m.submitted_at DESC
");
$stmt->bind_param("s", $faculty_email);
$stmt->execute();
$result = $stmt->get_result();
$results = [];
while ($row = $result->fetch_assoc()) {
    $results[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Exam Results - Faculty Dashboard</title>
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin:0;
    background-color: #f4f7f9;
    color: #2c3e50;
}
.navbar {
    background:#2c3e50;
    padding:12px 20px;
    color:white;
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    box-shadow:0 2px 4px rgba(0,0,0,0.1);
}
.navbar a {
    color:white;
    text-decoration:none;
    margin-left:15px;
    font-weight:bold;
}
.navbar a:hover { color:#1abc9c; }
.container {
    max-width:1100px;
    margin:20px auto;
    padding:20px;
}
.header {
    margin-bottom:20px;
    text-align:center;
}
.header h1 {
    margin:0;
    font-size:28px;
    color:#34495e;
}
.table-wrapper {
    overflow-x:auto;
}
.table {
    width:100%;
    border-collapse:collapse;
    background:#fff;
    border-radius:10px;
    overflow:hidden;
    box-shadow:0 2px 10px rgba(0,0,0,0.1);
    min-width:700px;
}
.table th, .table td {
    padding:12px 15px;
    text-align:left;
    border-bottom:1px solid #ddd;
}
.table th {
    background:#3498db;
    color:white;
    font-weight:600;
}
.table tr:last-child td { border-bottom:none; }
.no-data {
    text-align:center;
    font-style:italic;
    color:#888;
    margin-top:30px;
}

/* Responsive adjustments */
@media(max-width:768px){
    .header h1 { font-size:24px; }
    .table th, .table td { padding:10px; font-size:14px; }
}
@media(max-width:480px){
    .navbar { flex-direction:column; align-items:flex-start; }
    .navbar a { margin:5px 0 0 0; }
    .header h1 { font-size:20px; }
    .table th, .table td { padding:8px; font-size:12px; }
}
</style>
</head>
<body>

<div class="navbar">
    <div>Faculty: <?= htmlspecialchars($faculty_email) ?></div>
    <div>
        <a href="faculty_front_page.php">Dashboard</a>
        <a href="http://localhost/Exam_Management_System/Ems_start/frontpage.php">Logout</a>
    </div>
</div>

<div class="container">
    <div class="header">
        <h1>Students' Exam Results</h1>
        <p>Showing results only for exams you have created.</p>
    </div>

    <?php if (!empty($results)): ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Student Roll Number</th>
                        <th>Exam Title</th>
                        <th>Exam Date</th>
                        <th>Marks Obtained</th>
                        <th>Total Marks</th>
                        <th>Submitted At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $index => $res): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($res['roll_number']) ?></td>
                            <td><?= htmlspecialchars($res['test_title']) ?></td>
                            <td><?= htmlspecialchars($res['test_date']) ?></td>
                            <td><?= $res['marks_obtained'] ?></td>
                            <td><?= $res['total_marks'] ?></td>
                            <td><?= $res['submitted_at'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="no-data">No students have submitted exams for the tests you created yet.</p>
    <?php endif; ?>
</div>

</body>
</html>
