<?php
session_start();
if (!isset($_SESSION['faculty_user'])) {
    die("Please login first!");
}

$faculty_email = $_SESSION['faculty_user'];

$host = "localhost";
$user = "root";
$pass = "";
$db_name = "room_allocation";
$conn = new mysqli($host, $user, $pass, $db_name);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";

// Get faculty number_of_duties
$faculty = $conn->query("SELECT * FROM faculty_duty_done WHERE email_id='$faculty_email'")->fetch_assoc();
$min_duties = $faculty['number_of_duties'] ?? 0;

// Handle form submission
if (isset($_POST['submit_slots'])) {
    $selected_slots = $_POST['slots'] ?? [];
    
    if (count($selected_slots) < $min_duties) {
        $message = "❌ You must select at least $min_duties slots.";
    } else {
        foreach ($selected_slots as $slot_id) {
            // Check if slot already full
            $check_slot = $conn->query("SELECT max_capacity FROM free_slots WHERE id='$slot_id'")->fetch_assoc();
            $capacity = $check_slot['max_capacity'];

            $taken = $conn->query("SELECT COUNT(*) AS count FROM faculty_slot_selection WHERE slot_id='$slot_id'")->fetch_assoc()['count'];

            if ($taken >= $capacity) {
                $message .= "⚠️ Slot ID $slot_id is already full!<br>";
                continue;
            }

            // Prevent duplicate selection
            $exists = $conn->query("SELECT * FROM faculty_slot_selection WHERE faculty_email='$faculty_email' AND slot_id='$slot_id'");
            if ($exists->num_rows > 0) continue;

            $slot = $conn->query("SELECT * FROM free_slots WHERE id='$slot_id'")->fetch_assoc();

            $stmt = $conn->prepare("
                INSERT INTO faculty_slot_selection 
                (faculty_email, slot_id, slot_date, slot_time, selected_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("siss", $faculty_email, $slot['id'], $slot['slot_date'], $slot['slot_time']);
            $stmt->execute();
            $stmt->close();
        }
        if ($message == "") {
            $message = "✅ Slots selected successfully!";
        }
    }
}

// Fetch all available slots
$slots = $conn->query("SELECT * FROM free_slots ORDER BY slot_date ASC");

// Fetch already selected slots for this faculty
$selected_slots = [];
$result = $conn->query("SELECT slot_id FROM faculty_slot_selection WHERE faculty_email='$faculty_email'");
while ($row = $result->fetch_assoc()) {
    $selected_slots[] = $row['slot_id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Faculty Slot Selection</title>
<style>
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f8; padding: 20px; }
.container { width: 80%; margin: auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.2); }
h2 { text-align:center; color:#003366; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { border: 1px solid #ddd; padding: 10px; text-align:center; }
th { background: #003366; color: white; }
button { background: #003366; color: white; padding: 10px 20px; border:none; border-radius:5px; cursor:pointer; margin-top: 15px; }
button:hover { background: #0055aa; }
.message { text-align:center; font-weight:bold; color:red; margin-top:15px; }
.full { color: red; font-weight: bold; }
.disabled { color: gray; }
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
.back-btn:hover { background: #2d3436; }
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
