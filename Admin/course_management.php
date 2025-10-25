<?php
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['admin_user'])) {
    header("Location: admin_login.php");
    exit;
}

// Database connection (render-ready using env variables)
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'course_registration_data';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("<h2 style='color:red;'>Database connection failed: " . $conn->connect_error . "</h2>");
}

// Fetch all courses
$courses = $conn->query("SELECT * FROM admin_courses ORDER BY created_at DESC");

// Fetch all faculty for dropdown (from another DB)
$faculty_conn = new mysqli($db_host, $db_user, $db_pass, getenv('FACULTY_DB') ?: 'new_registration_data');
$faculty_list = $faculty_conn->query("SELECT faculty_id, first_name, last_name, email FROM faculty_new_data ORDER BY first_name ASC");
$faculty_data = [];
while ($f = $faculty_list->fetch_assoc()) {
    $faculty_data[] = $f;
}
$faculty_conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<title>Course Management</title>
<style>
  body {
    font-family: 'Segoe UI', sans-serif;
    background-color: #f5f8fc;
    margin: 0;
    padding: 0;
  }

  h2 {
    color: #003366;
    text-align: center;
    margin: 20px 10px;
    font-size: 28px;
  }

  .container {
    width: 90%;
    max-width: 1200px;
    margin: 20px auto;
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
  }

  form {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    justify-content: center;
    margin-bottom: 20px;
  }

  input,
  textarea,
  select {
    padding: 10px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 6px;
  }

  input[type="text"],
  select {
    width: 220px;
    max-width: 100%;
  }

  textarea {
    width: 100%;
    max-width: 300px;
    resize: vertical;
  }

  button {
    background-color: #0066cc;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
  }

  button:hover {
    background-color: #004c99;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 25px;
    overflow-x: auto;
  }

  th,
  td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: center;
    font-size: 14px;
  }

  th {
    background-color: #003366;
    color: white;
  }

  tr:nth-child(even) {
    background-color: #f2f6fa;
  }

  .delete-btn {
    background-color: crimson;
  }

  .delete-btn:hover {
    background-color: darkred;
  }

  .assign-section {
    text-align: center;
    margin-top: 10px;
  }

  .search-box {
    position: relative;
    text-align: right;
    margin-bottom: 10px;
  }

  .search-box input {
    padding: 8px 10px;
    width: 220px;
    border: 1px solid #aaa;
    border-radius: 6px;
  }

  @media screen and (max-width: 768px) {
    h2 {
      font-size: 24px;
    }

    .container {
      padding: 15px;
    }

    form {
      flex-direction: column;
      align-items: center;
    }

    input[type="text"],
    select,
    textarea {
      width: 100%;
      max-width: 100%;
    }

    .search-box {
      text-align: center;
      margin-top: 10px;
    }

    .search-box input {
      width: 100%;
      max-width: 300px;
    }

    table,
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

    tr {
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 8px;
      padding: 10px;
      background: #fff;
    }

    td {
      text-align: left;
      padding: 8px 10px;
      position: relative;
    }

    td::before {
      content: attr(data-label);
      font-weight: bold;
      color: #003366;
      display: block;
      margin-bottom: 5px;
    }

    .assign-section select {
      width: 100%;
    }

    .assign-section button {
      width: 100%;
      margin-top: 8px;
    }
  }

  @media screen and (max-width: 480px) {
    h2 {
      font-size: 22px;
    }

    button {
      font-size: 13px;
      padding: 8px 16px;
    }

    .delete-btn {
      padding: 8px 10px;
      font-size: 13px;
    }
  }
</style>
</head>
<body>

<h2>📘 Course Management</h2>

<div class="container">
    <form method="POST">
        <input type="text" name="course_name" placeholder="Course Name" required>
        <input type="text" name="course_code" placeholder="Course Code" required>
        <textarea name="description" placeholder="Description (Optional)" rows="1" cols="25"></textarea>
        <button type="submit" name="add_course">➕ Add Course</button>
    </form>

    <div class="search-box">
        <input type="text" id="facultySearch" placeholder="🔍 Search Faculty or Course...">
    </div>

    <table>
        <tr>
            <th>Course ID</th>
            <th>Name</th>
            <th>Code</th>
            <th>Description</th>
            <th>Assigned Faculty</th>
            <th>Action</th>
        </tr>

        <?php while ($row = $courses->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['course_id']) ?></td>
            <td><?= htmlspecialchars($row['course_name']) ?></td>
            <td><?= htmlspecialchars($row['course_code']) ?></td>
            <td><?= htmlspecialchars($row['description'] ?? '-') ?></td>
            <td>
                <?php if (!empty($row['assigned_faculty_name'])): ?>
                    <?= htmlspecialchars($row['assigned_faculty_name']) ?><br>
                    <small><?= htmlspecialchars($row['assigned_faculty_email']) ?></small>
                <?php else: ?>
                    <form method="POST" class="assign-section">
                        <input type="hidden" name="course_id" value="<?= $row['course_id'] ?>">
                        <select name="faculty_id" required>
                            <option value="">-- Select Faculty --</option>
                            <?php foreach ($faculty_data as $f): ?>
                                <option value="<?= $f['faculty_id'] ?>">
                                    <?= htmlspecialchars($f['first_name'] . ' ' . $f['last_name']) ?> (<?= htmlspecialchars($f['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="assign_faculty">Assign</button>
                    </form>
                <?php endif; ?>
            </td>
            <td>
                <a href="?delete=<?= $row['course_id'] ?>" onclick="return confirm('Delete this course?')" class="delete-btn" style="color:white;text-decoration:none;padding:6px 12px;border-radius:5px;">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <div style="text-align:center; margin-top:20px;">
        <a href="admin_front_page.php" 
           style="display:inline-block; background:#0066cc; color:white; padding:10px 20px; border-radius:6px; text-decoration:none;">
           ← Return to Dashboard
        </a>
    </div>
</div>

<script>
// Simple search for faculty or course
document.getElementById("facultySearch").addEventListener("keyup", function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll("table tr").forEach((row, index) => {
        if (index === 0) return;
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? "" : "none";
    });
});
</script>

</body>
</html>

<?php $conn->close(); ?>
