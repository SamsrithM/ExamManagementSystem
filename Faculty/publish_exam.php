<?php
session_start();

// Redirect if not logged in
if(!isset($_SESSION['faculty_user'])) {
    header("Location: faculty_login.php");
    exit;
}

$faculty_email = $_SESSION['faculty_user']; // logged-in faculty email

// Database connection
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$test_db_name = "test_creation";
$course_db_name = "course_registration_data";

$test_db = new mysqli($db_host, $db_user, $db_pass, $test_db_name);
if($test_db->connect_error) die("Test DB Connection failed: ".$test_db->connect_error);

$course_db = new mysqli($db_host, $db_user, $db_pass, $course_db_name);
if($course_db->connect_error) die("Course DB Connection failed: ".$course_db->connect_error);

// Handle publish form submission
if(isset($_POST['publish'])) {
    $test_id = $_POST['test_id'];
    $course_code = $_POST['course_code'];

    // Check if already published
    $stmt = $test_db->prepare("SELECT * FROM published_exam WHERE test_id=? AND course_code=? AND faculty_mail=?");
    $stmt->bind_param("iss", $test_id, $course_code, $faculty_email);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows == 0) {
        // Insert into published_exam
        $published_at = date('Y-m-d H:i:s');
        $stmt2 = $test_db->prepare("INSERT INTO published_exam (test_id, course_code, faculty_mail, published_at) VALUES (?,?,?,?)");
        $stmt2->bind_param("isss", $test_id, $course_code, $faculty_email, $published_at);
        $stmt2->execute();
        $stmt2->close();
        $msg = "✅ Exam published successfully!";
    } else {
        $msg = "⚠️ Already published!";
    }
    $stmt->close();
}

// Fetch tests created by this faculty
$tests = [];
$stmt = $test_db->prepare("SELECT * FROM tests WHERE created_by=? ORDER BY test_date ASC");
$stmt->bind_param("s", $faculty_email);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $tests[] = $row;
}
$stmt->close();

// Fetch courses assigned to this faculty
$courses = [];
$stmt = $course_db->prepare("SELECT course_code, course_name FROM admin_courses WHERE assigned_faculty_email=?");
$stmt->bind_param("s", $faculty_email);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) {
    $courses[] = $row;
}
$stmt->close();

// Fetch already published exams for this faculty
$published = [];
$stmt = $test_db->prepare("SELECT test_id, course_code FROM published_exam WHERE faculty_mail=?");
$stmt->bind_param("s", $faculty_email);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $published[$row['test_id']][$row['course_code']] = true;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Publish Exam</title>
<style>
body { font-family: Arial, sans-serif; background:#f4f7f9; padding:20px; }
h2 { color:#2c3e50; }
table { border-collapse: collapse; width:100%; margin-top:15px; background:white; }
th, td { border:1px solid #ddd; padding:8px; text-align:left; }
th { background:#1abc9c; color:white; }
tr:hover { background:#f1f1f1; }
select, button { padding:6px 10px; margin:2px; }
.msg { margin:10px 0; color:green; }
.published { color:green; font-weight:bold; }
.back-btn { display:inline-block; margin-top:20px; padding:8px 14px; background:#1abc9c; color:white; text-decoration:none; border-radius:5px; }
.back-btn:hover { background:#16a085; }
</style>
</head>
<body>

<h2>📌 Publish Exam</h2>

<?php if(isset($msg)) echo "<div class='msg'>$msg</div>"; ?>

<table>
<thead>
<tr>
<th>Test Title</th>
<th>Test Date</th>
<th>Available From</th>
<th>Duration</th>
<th>Test Type</th>
<th>Course</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php if(!empty($tests)): ?>
    <?php foreach($tests as $test): ?>
        <tr>
            <td><?= htmlspecialchars($test['test_title']) ?></td>
            <td><?= htmlspecialchars($test['test_date']) ?></td>
            <td><?= htmlspecialchars($test['available_from']) ?></td>
            <td><?= htmlspecialchars($test['duration']) ?></td>
            <td><?= htmlspecialchars($test['test_type']) ?></td>
            <td>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="test_id" value="<?= $test['test_id'] ?>">
                    <select name="course_code" required>
                        <option value="">Select Course</option>
                        <?php foreach($courses as $course): ?>
                            <option value="<?= htmlspecialchars($course['course_code']) ?>">
                                <?= htmlspecialchars($course['course_code'] . " - " . $course['course_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
            </td>
            <td>
                    <button type="submit" name="publish">Publish</button>
                    <?php 
                        if(isset($published[$test['test_id']])) {
                            foreach($published[$test['test_id']] as $code => $val) {
                                if($val) echo "<span class='published'>✅ $code</span> ";
                            }
                        }
                    ?>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
<tr><td colspan="7">No tests created by you yet.</td></tr>
<?php endif; ?>
</tbody>
</table>

<a href="faculty_front_page.php" class="back-btn">⬅️ Back to Dashboard</a>

</body>
</html>
