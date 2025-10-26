<?php
session_start();

if (!isset($_SESSION['roll_number'])) {
    header("Location: student_login.php");
    exit;
}

$roll_number = $_SESSION['roll_number'];

// --- DB connection using environment variables ---
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'test_creation';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch all results for the logged-in student
$stmt = $conn->prepare("
    SELECT m.test_id, m.marks_obtained, m.total_marks, m.submitted_at, t.test_title, t.test_date
    FROM marks_awarded m
    JOIN tests t ON m.test_id = t.test_id
    WHERE m.roll_number=?
    ORDER BY m.submitted_at DESC
");
$stmt->bind_param("s", $roll_number);
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
<title>Exam Results - Student Dashboard</title>
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
    max-width:1000px;
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
.table {
    width:100%;
    border-collapse:collapse;
    background:#fff;
    border-radius:10px;
    overflow:hidden;
    box-shadow:0 2px 10px rgba(0,0,0,0.1);
}
.table th, .table td {
    padding:15px;
    text-align:left;
    border-bottom:1px solid #ddd;
}
.table th {
    background:#1abc9c;
    color:white;
    font-weight:600;
}
.table tr:last-child td {
    border-bottom:none;
}
.view-btn {
    padding:6px 12px;
    background:#3498db;
    color:white;
    border:none;
    border-radius:5px;
    cursor:pointer;
    text-decoration:none;
}
.view-btn:hover {
    background:#2980b9;
}
.no-data {
    text-align:center;
    font-style:italic;
    color:#888;
    margin-top:30px;
}
@media(max-width:600px){
    .table th, .table td { padding:10px; font-size:14px; }
    .header h1 { font-size:22px; }
}
</style>
</head>
<body>

<div class="navbar">
    <div>Student: <?= htmlspecialchars($roll_number) ?></div>
    <div>
        <a href="student_front_page.php">Dashboard</a>
        <a href="/index.php">Logout</a>
    </div>
</div>

<div class="container">
    <div class="header">
        <h1>Your Exam Results</h1>
    </div>

    <?php if (!empty($results)): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
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
                        <td><?= htmlspecialchars($res['test_title']) ?></td>
                        <td><?= htmlspecialchars($res['test_date']) ?></td>
                        <td><?= $res['marks_obtained'] ?></td>
                        <td><?= $res['total_marks'] ?></td>
                        <td><?= $res['submitted_at'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-data">You have not taken any exams yet.</p>
    <?php endif; ?>
</div>

</body>
</html>
