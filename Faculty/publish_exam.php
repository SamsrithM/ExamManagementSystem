<?php
session_start();

// Redirect if not logged in
if(!isset($_SESSION['faculty_user'])) {
    header("Location: faculty_login.php");
    exit;
}

$faculty_email = $_SESSION['faculty_user']; // logged-in faculty email

// Detect environment
$is_render = getenv('RENDER') ? true : false;

// DB credentials
$db_host = getenv('DB_HOST') ?: ($is_render ? 'your_postgres_host' : 'localhost');
$db_user = getenv('DB_USER') ?: ($is_render ? 'postgres' : 'root');
$db_pass = getenv('DB_PASS') ?: '';
$test_db_name   = getenv('TEST_DB') ?: 'test_creation';
$course_db_name = getenv('COURSE_DB') ?: 'course_registration_data';

$msg = "";
$tests = $courses = $published = [];

// --- Database connection ---
if ($is_render) {
    // PostgreSQL connection
    $test_db = pg_connect("host=$db_host dbname=$test_db_name user=$db_user password=$db_pass");
    $course_db = pg_connect("host=$db_host dbname=$course_db_name user=$db_user password=$db_pass");
    if (!$test_db || !$course_db) die("Postgres connection failed.");

    // Handle publish
    if(isset($_POST['publish'])) {
        $test_id = $_POST['test_id'];
        $course_code = $_POST['course_code'];

        $res = pg_prepare($test_db, "check_publish", "SELECT * FROM published_exam WHERE test_id=$1 AND course_code=$2 AND faculty_mail=$3");
        $res = pg_execute($test_db, "check_publish", [$test_id, $course_code, $faculty_email]);

        if(pg_num_rows($res) == 0) {
            $published_at = date('Y-m-d H:i:s');
            $ins = pg_prepare($test_db, "insert_publish", "INSERT INTO published_exam (test_id, course_code, faculty_mail, published_at) VALUES ($1,$2,$3,$4)");
            pg_execute($test_db, "insert_publish", [$test_id, $course_code, $faculty_email, $published_at]);
            $msg = "✅ Exam published successfully!";
        } else {
            $msg = "⚠️ Already published!";
        }
    }

    // Fetch tests
    $res = pg_prepare($test_db, "fetch_tests", "SELECT * FROM tests WHERE created_by=$1 ORDER BY test_date ASC");
    $res = pg_execute($test_db, "fetch_tests", [$faculty_email]);
    while($row = pg_fetch_assoc($res)) $tests[] = $row;

    // Fetch courses
    $res = pg_prepare($course_db, "fetch_courses", "SELECT course_code, course_name FROM admin_courses WHERE assigned_faculty_email=$1");
    $res = pg_execute($course_db, "fetch_courses", [$faculty_email]);
    while($row = pg_fetch_assoc($res)) $courses[] = $row;

    // Fetch published exams
    $res = pg_prepare($test_db, "fetch_published", "SELECT test_id, course_code FROM published_exam WHERE faculty_mail=$1");
    $res = pg_execute($test_db, "fetch_published", [$faculty_email]);
    while($row = pg_fetch_assoc($res)) $published[$row['test_id']][$row['course_code']] = true;

} else {
    // MySQL connection
    $test_db = new mysqli($db_host, $db_user, $db_pass, $test_db_name);
    if($test_db->connect_error) die("Test DB Connection failed: ".$test_db->connect_error);
    $course_db = new mysqli($db_host, $db_user, $db_pass, $course_db_name);
    if($course_db->connect_error) die("Course DB Connection failed: ".$course_db->connect_error);

    // Handle publish
    if(isset($_POST['publish'])) {
        $test_id = $_POST['test_id'];
        $course_code = $_POST['course_code'];

        $stmt = $test_db->prepare("SELECT * FROM published_exam WHERE test_id=? AND course_code=? AND faculty_mail=?");
        $stmt->bind_param("iss", $test_id, $course_code, $faculty_email);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows == 0) {
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

    // Fetch tests
    $stmt = $test_db->prepare("SELECT * FROM tests WHERE created_by=? ORDER BY test_date ASC");
    $stmt->bind_param("s", $faculty_email);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) $tests[] = $row;
    $stmt->close();

    // Fetch courses
    $stmt = $course_db->prepare("SELECT course_code, course_name FROM admin_courses WHERE assigned_faculty_email=?");
    $stmt->bind_param("s", $faculty_email);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) $courses[] = $row;
    $stmt->close();

    // Fetch published exams
    $stmt = $test_db->prepare("SELECT test_id, course_code FROM published_exam WHERE faculty_mail=?");
    $stmt->bind_param("s", $faculty_email);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) $published[$row['test_id']][$row['course_code']] = true;
    $stmt->close();

    $test_db->close();
    $course_db->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Publish Exam</title>
<style>
body { 
  font-family: Arial, sans-serif; 
  background: #f4f7f9; 
  padding: 20px; 
}

h2 { 
  color: #2c3e50; 
  text-align: center; 
  margin-bottom: 20px; 
}

table { 
  border-collapse: collapse; 
  width: 100%; 
  margin-top: 15px; 
  background: white; 
  box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
}

th, td { 
  border: 1px solid #ddd; 
  padding: 8px; 
  text-align: left; 
}

th { 
  background: #1abc9c; 
  color: white; 
  font-weight: 600; 
}

tr:hover { 
  background: #f1f1f1; 
}

select, button { 
  padding: 6px 10px; 
  margin: 2px; 
  border-radius: 5px; 
  border: 1px solid #ccc; 
}

button { 
  background: #1abc9c; 
  color: white; 
  cursor: pointer; 
  transition: background 0.3s ease, transform 0.2s ease; 
}

button:hover { 
  background: #16a085; 
  transform: scale(1.05); 
}

.msg { 
  margin: 10px 0; 
  color: green; 
  font-weight: bold; 
  text-align: center; 
}

.published { 
  color: green; 
  font-weight: bold; 
  margin-left: 5px; 
}

.back-btn { 
  display: inline-block; 
  margin-top: 20px; 
  padding: 10px 18px; 
  background: #1abc9c; 
  color: white; 
  text-decoration: none; 
  border-radius: 6px; 
  transition: background 0.3s ease; 
}

.back-btn:hover { 
  background: #16a085; 
}

/* Responsive */
@media (max-width: 768px) {
  table, thead, tbody, th, td, tr { 
    display: block; 
    width: 100%; 
  }

  th { 
    display: none; 
  }

  td { 
    padding: 10px; 
    border: none; 
    position: relative; 
    padding-left: 50%; 
    margin-bottom: 10px; 
    background: #f9f9f9; 
    border-radius: 6px; 
  }

  td:before { 
    position: absolute; 
    left: 15px; 
    top: 10px; 
    width: 45%; 
    white-space: nowrap; 
    font-weight: bold; 
    content: attr(data-label); 
  }

  select, button { 
    width: 100%; 
    margin-top: 5px; 
  }
}
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
