<?php
session_start();

// Ensure student is logged in
if (!isset($_SESSION['student_user'])) {
    header("Location: student_login.php");
    exit;
}

// Render-ready DB connection
$db_host = getenv('DB_HOST') ?: 'mysql';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_MANAGE') ?: 'course_management';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("<h2 style='color:red;'>Connection failed: " . $conn->connect_error . "</h2>");
}

// Fetch courses assigned to the student
$student_id = $_SESSION['student_user'];
$stmt = $conn->prepare("SELECT course_code, course_name, credits, semester FROM student_courses WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$courses = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Batch & Course Assignment</title>
<style>
  body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #e0f7fa, #f1f8e9);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 40px 20px;
  }

  h1 {
    color: #00695c;
    text-align: center;
    margin-bottom: 20px;
    font-size: 28px;
  }

  form {
    width: 100%;
    max-width: 900px;
  }

  label {
    display: inline-block;
    margin: 10px;
    font-weight: 600;
    color: #333;
  }

  input[type="text"] {
    padding: 8px;
    border-radius: 6px;
    border: 1px solid #ccc;
    width: 180px;
    max-width: 100%;
  }

  .course-table {
    margin-top: 40px;
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  }

  .course-table th {
    background: #009688;
    color: white;
    padding: 12px;
    text-align: left;
    font-size: 16px;
  }

  .course-table td {
    padding: 10px;
    border-bottom: 1px solid #eee;
    font-size: 15px;
  }

  .course-table tr:hover {
    background: #f1f1f1;
  }

  .assign-btn,
  .back-btn {
    display: inline-block;
    margin-top: 25px;
    background: #00796b;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: background 0.3s;
    font-size: 16px;
  }

  .assign-btn:hover,
  .back-btn:hover {
    background: #004d40;
  }

  .notification {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #00796b;
    color: white;
    padding: 20px 30px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    font-size: 18px;
    text-align: center;
    z-index: 9999;
    opacity: 0;
    transition: opacity 0.3s;
  }

  .notification.show {
    opacity: 1;
  }

  /* Responsive Styles */
  @media screen and (max-width: 768px) {
    h1 {
      font-size: 24px;
    }

    label {
      display: block;
      margin: 10px 0;
    }

    input[type="text"] {
      width: 100%;
    }

    .assign-btn,
    .back-btn {
      width: 100%;
      font-size: 15px;
    }

    .course-table {
      font-size: 14px;
    }

    .course-table th,
    .course-table td {
      padding: 10px;
    }
  }

  @media screen and (max-width: 480px) {
    body {
      padding: 20px 10px;
    }

    h1 {
      font-size: 22px;
    }

    .assign-btn,
    .back-btn {
      padding: 10px;
      font-size: 14px;
    }

    .course-table th,
    .course-table td {
      font-size: 13px;
      padding: 8px;
    }
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
