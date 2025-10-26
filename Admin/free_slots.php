<?php
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['admin_user'])) {
    header("Location: admin_login.php");
    exit;
}

// Database connection
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_ROOM') ?: 'room_allocation';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("<h2 style='color:red;'>Connection failed: " . $conn->connect_error . "</h2>");
}

$notification = "";

// ==========================
// Add Exam Slot
// ==========================
if (isset($_POST['submit_slot'])) {
    $exam_type = $_POST['exam_type'] ?? '';
    $slot_date = $_POST['slot_date'];
    $slot_start = $_POST['slot_start'];
    $slot_end = $_POST['slot_end'];
    $max_capacity = intval($_POST['max_capacity']);
    $slot_time = "$slot_start - $slot_end";

    $stmt = $conn->prepare(
        "INSERT INTO free_slots (exam_type, slot_date, slot_time, max_capacity) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("sssi", $exam_type, $slot_date, $slot_time, $max_capacity);
    $stmt->execute();
    $stmt->close();

    $notification = "✅ Slot added successfully!";
}

// ==========================
// Delete Exam Slot
// ==========================
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $conn->query("DELETE FROM faculty_slot_selection WHERE slot_id=$delete_id"); // remove dependencies
    $conn->query("DELETE FROM free_slots WHERE id=$delete_id");
    $notification = "🗑️ Slot and related selections deleted successfully!";
}

// ==========================
// Fetch all slots (for viewing)
// ==========================
$slots = [];
$result = $conn->query("SELECT * FROM free_slots ORDER BY slot_date ASC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $slots[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<html lang="en">
<head>
<meta charset="UTF-8">
<title>Exam Slots Management</title>
<style>
  body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #6a11cb, #2575fc);
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
  }

  .container {
    background: #ffffff;
    margin-top: 60px;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    width: 90%;
    max-width: 900px;
  }

  h2 {
    text-align: center;
    color: #2d3436;
    font-size: 28px;
    margin-bottom: 10px;
  }

  .buttons {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin: 25px 0;
    flex-wrap: wrap;
  }

  button {
    padding: 12px 25px;
    font-size: 16px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    color: white;
    font-weight: 600;
    transition: 0.3s;
  }

  .enter-btn {
    background: #0984e3;
  }

  .enter-btn:hover {
    background: #74b9ff;
  }

  .view-btn {
    background: #00b894;
  }

  .view-btn:hover {
    background: #55efc4;
  }

  form {
    display: none;
    margin-top: 30px;
    text-align: center;
  }

  select,
  input[type="date"],
  input[type="time"],
  input[type="number"] {
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #b2bec3;
    margin: 10px;
    width: 45%;
    font-size: 15px;
    max-width: 100%;
  }

  .save-btn {
    margin-top: 25px;
    background: #6c5ce7;
    padding: 12px 30px;
    border-radius: 10px;
    font-size: 16px;
    color: white;
    border: none;
    cursor: pointer;
    transition: 0.3s;
    font-weight: 600;
  }

  .save-btn:hover {
    background: #a29bfe;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 30px;
    text-align: center;
    overflow-x: auto;
  }

  th,
  td {
    padding: 12px;
    border: 1px solid #dfe6e9;
    font-size: 14px;
  }

  th {
    background: #6c5ce7;
    color: white;
  }

  tr:nth-child(even) {
    background: #f1f2f6;
  }

  .delete-btn {
    background: #e17055;
    padding: 5px 12px;
    border-radius: 6px;
    font-weight: 600;
    color: white;
    text-decoration: none;
  }

  .delete-btn:hover {
    background: #d63031;
  }

  .back-btn {
    display: inline-block;
    background: #636e72;
    color: white;
    text-decoration: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    margin-top: 20px;
  }

  .back-btn:hover {
    background: #2d3436;
  }

  #notification {
    position: fixed;
    top: 40%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(50, 50, 50, 0.95);
    color: white;
    padding: 20px 35px;
    border-radius: 12px;
    font-size: 18px;
    font-weight: 600;
    display: none;
    z-index: 9999;
    text-align: center;
  }

  /* Responsive Styles */
  @media screen and (max-width: 768px) {
    .container {
      padding: 25px 20px;
    }

    .buttons {
      flex-direction: column;
      align-items: center;
    }

    select,
    input[type="date"],
    input[type="time"],
    input[type="number"] {
      width: 100%;
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
      color: #6c5ce7;
      display: block;
      margin-bottom: 5px;
    }
  }

  @media screen and (max-width: 480px) {
    h2 {
      font-size: 22px;
    }

    button,
    .save-btn,
    .back-btn {
      font-size: 14px;
      padding: 10px 16px;
    }

    td {
      font-size: 13px;
    }
  }
</style>
</head>
<body>
<div class="container">
<h2>🎯 Exam Slots Management</h2>
<div class="buttons">
    <button class="enter-btn" onclick="showForm()">➕ Enter Exam Slot</button>
    <button class="view-btn" onclick="window.location.href='free_slots.php?view=1'">👁️ View Exam Slots</button>
</div>

<!-- Add Slot Form -->
<form id="slotForm" method="POST">
    <h3>Add Exam Slot</h3>
    <label>Exam Type:</label>
    <select name="exam_type" required>
        <option value="">-- Select Exam Type --</option>
        <option value="Mid-Term">Mid-Term Exam</option>
        <option value="Semester">Semester Exam</option>
    </select><br>
    <label>Date:</label>
    <input type="date" name="slot_date" required><br>
    <label>Start Time:</label>
    <input type="time" name="slot_start" required>
    <label>End Time:</label>
    <input type="time" name="slot_end" required><br>
    <label>Max Capacity:</label>
    <input type="number" name="max_capacity" min="1" placeholder="Enter max capacity" required><br>
    <button type="submit" name="submit_slot" class="save-btn">💾 Save Slot</button>
</form>

<?php
// ==========================
// View Slots Section
// ==========================
if (isset($_GET['view'])) {
    $result = $conn->query("SELECT * FROM free_slots ORDER BY slot_date ASC");
    if ($result->num_rows > 0) {
        echo "<h3 style='text-align:center;margin-top:30px;'>📅 All Exam Slots</h3>";
        echo "<table><tr><th>ID</th><th>Date</th><th>Time</th><th>Max Capacity</th><th>Action</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['slot_date']}</td>
                    <td>{$row['slot_time']}</td>
                    <td>{$row['max_capacity']}</td>
                    <td><a href='free_slots.php?view=1&delete={$row['id']}' class='delete-btn' onclick='return confirm(\"Are you sure to delete this slot?\")'>Delete</a></td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='text-align:center;margin-top:20px;'>No Exam slots available yet.</p>";
    }

    echo "<div style='text-align:center;'><a href='admin_front_page.php' class='back-btn'>⬅️ Back to Dashboard</a></div>";
}
$conn->close();
?>

</div>

<!-- Notification -->
<div id="notification"><?php echo $notification; ?></div>

<script>
function showForm() {
    document.getElementById('slotForm').style.display = 'block';
}

// Show notification if there is a message
const notification = document.getElementById('notification');
if (notification.innerText.trim() !== "") {
    notification.style.display = 'block';
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => { notification.style.display = 'none'; notification.style.opacity='1'; }, 500);
    }, 2500);
}
</script>
</body>
</html>
