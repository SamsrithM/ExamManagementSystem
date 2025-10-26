<?php
session_start();
if (!isset($_SESSION['faculty_user'])) {
    header("Location: faculty_login.php"); // redirect to login
    exit;
}

$faculty_email = $_SESSION['faculty_user'];

// Database connection using environment variables
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'room_allocation';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";

// Get faculty number_of_duties using prepared statement
$stmt = $conn->prepare("SELECT number_of_duties FROM faculty_duty_done WHERE email_id=?");
$stmt->bind_param("s", $faculty_email);
$stmt->execute();
$faculty = $stmt->get_result()->fetch_assoc();
$stmt->close();
$min_duties = $faculty['number_of_duties'] ?? 0;

// Handle form submission
if (isset($_POST['submit_slots'])) {
    $selected_slots = $_POST['slots'] ?? [];

    if (count($selected_slots) < $min_duties) {
        $message = "❌ You must select at least $min_duties slots.";
    } else {
        foreach ($selected_slots as $slot_id) {
            // Check if slot already full
            $stmt = $conn->prepare("SELECT max_capacity, slot_date, slot_time FROM free_slots WHERE id=?");
            $stmt->bind_param("i", $slot_id);
            $stmt->execute();
            $slot = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM faculty_slot_selection WHERE slot_id=?");
            $stmt->bind_param("i", $slot_id);
            $stmt->execute();
            $taken = $stmt->get_result()->fetch_assoc()['count'];
            $stmt->close();

            if ($taken >= $slot['max_capacity']) {
                $message .= "⚠️ Slot ID $slot_id is already full!<br>";
                continue;
            }

            // Prevent duplicate selection
            $stmt = $conn->prepare("SELECT 1 FROM faculty_slot_selection WHERE faculty_email=? AND slot_id=?");
            $stmt->bind_param("si", $faculty_email, $slot_id);
            $stmt->execute();
            $exists = $stmt->get_result();
            $stmt->close();
            if ($exists->num_rows > 0) continue;

            // Insert slot selection
            $stmt = $conn->prepare("
                INSERT INTO faculty_slot_selection 
                (faculty_email, slot_id, slot_date, slot_time, selected_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("siss", $faculty_email, $slot_id, $slot['slot_date'], $slot['slot_time']);
            $stmt->execute();
            $stmt->close();
        }

        if ($message == "") $message = "✅ Slots selected successfully!";
    }
}

// Fetch all available slots
$slots = $conn->query("SELECT * FROM free_slots ORDER BY slot_date ASC");

// Fetch already selected slots for this faculty
$selected_slots = [];
$stmt = $conn->prepare("SELECT slot_id FROM faculty_slot_selection WHERE faculty_email=?");
$stmt->bind_param("s", $faculty_email);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $selected_slots[] = $row['slot_id'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Faculty Slot Selection</title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f6f8;
    padding: 20px;
  }

  .container {
    width: 100%;
    max-width: 1000px;
    margin: auto;
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.2);
  }

  h2 {
    text-align: center;
    color: #003366;
    font-size: 26px;
    margin-bottom: 10px;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    overflow-x: auto;
  }

  th, td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: center;
    font-size: 15px;
  }

  th {
    background: #003366;
    color: white;
    font-size: 14px;
  }

  button {
    background: #003366;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 15px;
    font-size: 15px;
    font-weight: 600;
  }

  button:hover {
    background: #0055aa;
  }

  .message {
    text-align: center;
    font-weight: bold;
    color: red;
    margin-top: 15px;
    font-size: 16px;
  }

  .full {
    color: red;
    font-weight: bold;
  }

  .disabled {
    color: gray;
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
    font-size: 15px;
  }

  .back-btn:hover {
    background: #2d3436;
  }

  h3 {
    margin-top: 40px;
    color: #003366;
    text-align: center;
    font-size: 20px;
  }

  @media screen and (max-width: 768px) {
    .container {
      padding: 15px;
    }

    h2 {
      font-size: 22px;
    }

    th, td {
      font-size: 14px;
      padding: 8px;
    }

    button, .back-btn {
      font-size: 14px;
      padding: 8px 16px;
    }

    h3 {
      font-size: 18px;
    }
  }

  @media screen and (max-width: 480px) {
    table, thead, tbody, th, td, tr {
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

    .message {
      font-size: 15px;
    }

    h2 {
      font-size: 20px;
    }

    h3 {
      font-size: 17px;
    }
  }
</style>
</head>
<body>

<div class="container">
<h2>📅 Select Exam Slots</h2>
<p class="message"><?= $message ?></p>

<form method="POST">
    <table>
        <tr>
            <th>Select</th>
            <th>Date</th>
            <th>Time</th>
            <th>Max Capacity</th>
            <th>Filled</th>
            <th>Status</th>
        </tr>
        <?php if ($slots->num_rows > 0): ?>
            <?php while($row = $slots->fetch_assoc()):
                $slot_id = $row['id'];
                $capacity = $row['max_capacity'];
                $taken = $conn->query("SELECT COUNT(*) AS count FROM faculty_slot_selection WHERE slot_id='$slot_id'")->fetch_assoc()['count'];
                $full = $taken >= $capacity;
            ?>
                <tr>
                    <td>
                        <?php if ($full): ?>
                            <input type="checkbox" disabled>
                        <?php elseif (in_array($slot_id, $selected_slots)): ?>
                            <input type="checkbox" checked disabled>
                        <?php else: ?>
                            <input type="checkbox" name="slots[]" value="<?= $slot_id ?>">
                        <?php endif; ?>
                    </td>
                    <td><?= $row['slot_date'] ?></td>
                    <td><?= $row['slot_time'] ?></td>
                    <td><?= $capacity ?></td>
                    <td><?= $taken ?></td>
                    <td><?= $full ? "<span class='full'>Full</span>" : "<span style='color:green;'>Available</span>" ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No slots available</td></tr>
        <?php endif; ?>
    </table>
    <button type="submit" name="submit_slots">Submit Slots</button>
</form>

<?php if (!empty($selected_slots)): ?>
<h3>Your Confirmed Slots</h3>
<table>
    <tr>
        <th>Date</th>
        <th>Time</th>
        <th>Selected At</th>
    </tr>
    <?php
    $confirmed = $conn->query("SELECT slot_date, slot_time, selected_at FROM faculty_slot_selection WHERE faculty_email='$faculty_email' ORDER BY selected_at DESC");
    while($row = $confirmed->fetch_assoc()): ?>
        <tr>
            <td><?= $row['slot_date'] ?></td>
            <td><?= $row['slot_time'] ?></td>
            <td><?= $row['selected_at'] ?></td>
        </tr>
    <?php endwhile; ?>
</table>
<?php endif; ?>

<div style="text-align:center;">
    <a href="faculty_front_page.php" class="back-btn">⬅️ Back to Dashboard</a>
</div>

</div>

</body>
</html>

<?php $conn->close(); ?>
