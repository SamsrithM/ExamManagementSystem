<?php
session_start(); // Start session for notifications

// --- Set correct timezone ---
date_default_timezone_set('Asia/Kolkata');

// --- DB Connection ---
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "course_registration_data";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("<h2 style='color:red;'>Database Connection Failed: " . $conn->connect_error . "</h2>");
}

// --- Handle Course Assignment Form ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_courses_students'])) {
    $program = $_POST['program'];
    $branch = $_POST['branch'];
    $batch_year = $_POST['batch_year'];
    $selected = $_POST['selected_courses'] ?? [];

    if (count($selected) === 0) {
        $_SESSION['notif_message'] = "⚠ Please select at least one course!";
        header("Location: course_management_student.php");
        exit;
    }

    // Fill up to 4 courses
    $courses = array_pad($selected, 4, null);

    $created_at = date('Y-m-d H:i:s');

    // Insert data WITHOUT faculty name
    $sql = "INSERT INTO assign_courses_students 
            (program, branch, batch_year, course1, course2, course3, course4, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param(
        "ssssssss",
        $program,
        $branch,
        $batch_year,
        $courses[0],
        $courses[1],
        $courses[2],
        $courses[3],
        $created_at
    );

    if (!$stmt->execute()) {
        die("Error inserting data: " . $stmt->error);
    }
    $stmt->close();

    $_SESSION['notif_message'] = "✅ Courses successfully assigned to $program $branch $batch_year!";
    header("Location: course_management_student.php");
    exit;
}

// --- Fetch all courses ---
$courses = [];
$sql = "SELECT course_id, course_code, assigned_faculty_name 
        FROM admin_courses
        ORDER BY course_id ASC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Batch & Course Assignment</title>
<style>
body { margin: 0; font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #e0f7fa, #f1f8e9); min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding: 40px 20px; }
h1 { color: #00695c; text-align: center; margin-bottom: 20px; }
.course-table { margin-top: 40px; width: 100%; max-width: 900px; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
.course-table th { background: #009688; color: white; padding: 12px; text-align: left; }
.course-table td { padding: 10px; border-bottom: 1px solid #eee; }
.course-table tr:hover { background: #f1f1f1; }
.assign-btn, .back-btn { display: inline-block; margin-top: 25px; background: #00796b; color: white; padding: 8px 18px; border-radius: 8px; text-decoration: none; border: none; cursor: pointer; transition: background 0.3s; }
.assign-btn:hover, .back-btn:hover { background: #004d40; }
label { margin-right: 10px; }

/* Notification style */
.notification {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #00796b;
    color: white;
    padding: 20px 30px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    font-size: 18px;
    text-align: center;
    z-index: 9999;
    opacity: 0;
    transition: opacity 0.3s;
}
.notification.show {
    opacity: 1;
}
</style>
</head>
<body>

<h1>🎓 Assign Courses to a Batch</h1>

<form action="course_management_student.php" method="POST">
  <label>Programme: <input type="text" name="program" required></label>
  <label>Branch: <input type="text" name="branch" required></label>
  <label>Batch Year: <input type="text" name="batch_year" required></label>

  <?php if (count($courses) > 0): ?>
  <table class="course-table">
    <tr>
      <th>Select</th>
      <th>Course ID</th>
      <th>Course Code</th>
      <th>Assigned Faculty</th>
    </tr>
    <?php foreach ($courses as $c): ?>
    <tr>
      <td><input type="checkbox" name="selected_courses[]" value="<?= htmlspecialchars($c['course_code']); ?>"></td>
      <td><?= htmlspecialchars($c['course_id']); ?></td>
      <td><?= htmlspecialchars($c['course_code']); ?></td>
      <td><?= htmlspecialchars($c['assigned_faculty_name']); ?></td>
    </tr>
    <?php endforeach; ?>
  </table>

  <button type="submit" name="assign_courses_students" class="assign-btn">✅ Assign Selected Courses</button>
  <?php else: ?>
  <p style="color:#777;">No courses available in the system.</p>
  <?php endif; ?>
</form>

<a href="admin_front_page.php" class="back-btn">⬅ Back to Dashboard</a>

<script>
function showNotification(message, duration = 2000) {
    const notif = document.createElement('div');
    notif.className = 'notification';
    notif.innerText = message;
    document.body.appendChild(notif);

    // Show notification
    setTimeout(() => notif.classList.add('show'), 10);

    // Hide and remove notification after duration
    setTimeout(() => {
        notif.classList.remove('show');
        setTimeout(() => notif.remove(), 300);
    }, duration);
}

// Display notification if PHP session has message
<?php if (isset($_SESSION['notif_message'])): ?>
    showNotification("<?= $_SESSION['notif_message']; ?>");
    <?php unset($_SESSION['notif_message']); ?>
<?php endif; ?>
</script>

</body>
</html>
