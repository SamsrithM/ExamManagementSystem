<?php
session_start();

if (!isset($_SESSION['roll_number'])) {
    header("Location: student_login.php");
    exit;
}

$roll_number = $_SESSION['roll_number'];
$course_code = isset($_GET['course_code']) ? $_GET['course_code'] : '';

if (empty($course_code)) {
    die("Invalid course selected.");
}

// DB connection
$db_host = "localhost";
$db_user = "root";
$db_pass = "";

$test_db = new mysqli($db_host, $db_user, $db_pass, "test_creation");
if ($test_db->connect_error) {
    die("Test DB connection failed: " . $test_db->connect_error);
}

// Get published exams for this course
$exams = [];
$stmt = $test_db->prepare("
    SELECT t.test_id, t.test_title, t.test_date, t.available_from, t.duration, t.test_type, p.published_at
    FROM published_exam p
    JOIN tests t ON p.test_id = t.test_id
    WHERE p.course_code = ?
    ORDER BY t.test_date ASC
");
$stmt->bind_param("s", $course_code);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $exams[] = $row;
}
$stmt->close();
$test_db->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Course Details - <?= htmlspecialchars($course_code) ?></title>
<style>
body { font-family: Arial, sans-serif; margin:0; background:#f4f7f9; }
nav { position: fixed; top:0; left:0; width:100%; background:#2c3e50; color:white; padding:12px 20px; box-shadow:0 2px 4px rgba(0,0,0,0.1); z-index:1000; }
nav h2 { margin:0; font-size:18px; color:white; }
nav a { color:white; text-decoration:none; margin-left:20px; font-weight:bold; }
nav a:hover { color:#1abc9c; }

.container { padding:80px 20px 20px 20px; max-width:900px; margin:auto; }

h2, h3 { color:#2c3e50; margin-bottom:10px; }
.card { background:white; padding:15px; margin:10px 0; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
.no-data { color:#888; font-style:italic; }
.back-btn { display:inline-block; margin-top:20px; padding:8px 14px; background:#1abc9c; color:white; text-decoration:none; border-radius:5px; }
.back-btn:hover { background:#16a085; }

/* Take Exam button */
.take-exam-btn {
    display:inline-block;
    margin-top:10px;
    padding:8px 14px;
    background:#e74c3c; /* red color */
    color:white;
    text-decoration:none;
    border-radius:5px;
    font-weight:bold;
}
.take-exam-btn:hover { background:#c0392b; }

/* Responsive adjustments */
@media(max-width:600px){
    nav { padding:10px 15px; }
    nav h2 { font-size:16px; }
    nav a { margin-left:10px; font-size:14px; }
    .card { padding:12px; font-size:14px; }
    .back-btn, .take-exam-btn { padding:6px 12px; font-size:14px; }
}
</style>
</head>
<body>

<nav>
    <h2>Course Code: <?= htmlspecialchars($course_code) ?></h2>
</nav>

<div class="container">

<div class="container">

<h3>📝 Published Exams</h3>

<?php if (empty($exams)): ?>
    <div class="card no-data">
        No exams have been published for this course yet.
    </div>
<?php else: ?>
    <?php 
    // Reuse DB connection for checking marks
    $test_db = new mysqli($db_host, $db_user, $db_pass, "test_creation");
    if ($test_db->connect_error) {
        die("Test DB connection failed: " . $test_db->connect_error);
    }
    ?>
    <?php foreach ($exams as $exam): 
        $stmt_check = $test_db->prepare("SELECT 1 FROM marks_awarded WHERE roll_number=? AND test_id=?");
        $stmt_check->bind_param("si", $roll_number, $exam['test_id']);
        $stmt_check->execute();
        $stmt_check->store_result();
        $already_taken = $stmt_check->num_rows > 0;
        $stmt_check->close();
    ?>
    <div class="card">
        <strong><?= htmlspecialchars($exam['test_title']) ?></strong><br>
        Date: <?= htmlspecialchars($exam['test_date']) ?> | Available From: <?= htmlspecialchars($exam['available_from']) ?><br>
        Duration: <?= htmlspecialchars($exam['duration']) ?> | Type: <?= htmlspecialchars($exam['test_type']) ?><br>
        Published At: <?= htmlspecialchars($exam['published_at']) ?><br>
        <?php
        $now = strtotime(date('Y-m-d H:i'));
        $exam_start = strtotime($exam['test_date'] . ' ' . $exam['available_from']);
        $exam_end = $exam_start + ($exam['duration'] * 60);

        // Can take exam only if not already taken AND current time is within exam window
        $can_take = (!$already_taken && $now >= $exam_start && $now <= $exam_end);

        // Remaining time in seconds (for take_exam.php to use)
        $remaining_time = $exam_end - $now;
        ?>

    <?php if ($already_taken): ?>
    <span style="color:#e74c3c; font-weight:bold;">Exam already taken</span>
    <?php elseif ($can_take): ?>
        <a href="take_exam.php?test_id=<?= $exam['test_id'] ?>&remaining=<?= $remaining_time ?>" class="take-exam-btn">Take Exam</a>
    <?php else: ?>
        <span style="color:#888; font-style:italic;">Exam not available</span>
    <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php $test_db->close(); ?>
<?php endif; ?>

</div>

</body>
</html>
