<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "room_allocation";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("<h2 style='color:red;'>Connection failed: " . $conn->connect_error . "</h2>");
}

$message = "";

// Handle form submission for adding new record
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['allocate'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $hours = trim($_POST['hours']);
    $num_duties = trim($_POST['num_duties']);

    if ($name === "" || $email === "" || $hours === "" || $num_duties === "") {
        $message = "<p style='color:red;'>All fields are required.</p>";
    } else {
        // Check if faculty already exists
        $check = $conn->prepare("SELECT * FROM faculty_duty_done WHERE email_id = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            // Existing record — cannot modify except number of duties via table
            $message = "<p style='color:red; font-weight:bold;'>❌ Record already exists for $name ($email). Number of duties can be modified below.</p>";
        } else {
            // Insert new record
            $insert = $conn->prepare("INSERT INTO faculty_duty_done (name, email_id, hours_of_duty_done, number_of_duties) VALUES (?, ?, ?, ?)");
            $insert->bind_param("ssii", $name, $email, $hours, $num_duties);
            if ($insert->execute()) {
                $message = "<p style='color:green; font-weight:bold;'>✅ Duty allocated successfully for $name.</p>";
            } else {
                $message = "<p style='color:red;'>Error adding record: " . $conn->error . "</p>";
            }
            $insert->close();
        }

        $check->close();
    }
}

// Handle update for number of duties
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_duties'])) {
    $update_id = $_POST['update_id']; // email_id
    $new_duties = $_POST['new_duties'];

    if ($new_duties === "") {
        $message = "<p style='color:red;'>Enter a valid number of duties.</p>";
    } else {
        $stmt = $conn->prepare("UPDATE faculty_duty_done SET number_of_duties = ? WHERE email_id = ?");
        $stmt->bind_param("is", $new_duties, $update_id);
        if ($stmt->execute()) {
            $message = "<p style='color:green; font-weight:bold;'>✅ Number of duties updated successfully.</p>";
        } else {
            $message = "<p style='color:red;'>Error updating record: " . $conn->error . "</p>";
        }
        $stmt->close();
    }
}

// Fetch all faculty duties
$duties = $conn->query("SELECT * FROM faculty_duty_done ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty Duty Allocation</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }
        h1 {
            background-color: #003366;
            color: white;
            text-align: center;
            padding: 15px 0;
        }
        .container {
            width: 85%;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            padding: 20px;
        }
        form {
            margin-bottom: 30px;
            text-align: center;
        }
        input[type="text"], input[type="email"], input[type="number"] {
            padding: 8px;
            margin: 5px;
            border-radius: 5px;
            border: 1px solid #999;
        }
        input[type="submit"] {
            background-color: #003366;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 5px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #0055aa;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
        }
        th {
            background-color: #003366;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .message {
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .note {
            text-align: center;
            color: red;
            font-weight: bold;
            font-size: 15px;
            margin-top: -10px;
        }
        .dashboard-btn {
            display: block;
            width: fit-content;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #cc0000;
            color: white;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }
        .dashboard-btn:hover {
            background-color: #ff3333;
        }
        .update-form input[type="number"] {
            width: 60px;
        }
        .update-form input[type="submit"] {
            padding: 5px 10px;
            font-size: 13px;
        }
    </style>
</head>
<body>

<h1>Faculty Duty Allocation Portal</h1>

<div class="container">
    <!-- Allocate New Duty -->
    <form method="POST">
        <h3>Allocate Faculty Duty</h3>
        <p class="note">⚠️ Once created, a record cannot be updated except number of duties below. Verify before submitting.</p>
        <input type="text" name="name" placeholder="Faculty Name" required>
        <input type="email" name="email" placeholder="Faculty Email" required>
        <input type="number" name="hours" placeholder="Hours of Duty" required>
        <input type="number" name="num_duties" placeholder="Number of Duties" required>
        <br>
        <input type="submit" name="allocate" value="Allocate Duty">
    </form>

    <div class="message"><?= $message ?></div>

    <!-- All Faculty Duties Table -->
    <h3 style="text-align:center;">All Faculty Duties</h3>
    <table>
        <tr>
            <th>Name</th>
            <th>Email ID</th>
            <th>Hours of Duty Done</th>
            <th>Number of Duties</th>
            <th>Modify Duties</th>
        </tr>
        <?php if ($duties->num_rows > 0): ?>
            <?php while($row = $duties->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['email_id']) ?></td>
                    <td><?= htmlspecialchars($row['hours_of_duty_done']) ?></td>
                    <td><?= htmlspecialchars($row['number_of_duties']) ?></td>
                    <td>
                        <form method="POST" class="update-form">
                            <input type="hidden" name="update_id" value="<?= htmlspecialchars($row['email_id']) ?>">
                            <input type="number" name="new_duties" min="0" value="<?= htmlspecialchars($row['number_of_duties']) ?>" required>
                            <input type="submit" name="update_duties" value="Update">
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5" style="color:red;">No faculty duties allocated yet.</td></tr>
        <?php endif; ?>
    </table>

    <!-- Return to Dashboard Button -->
    <a href="admin_front_page.php" class="dashboard-btn">⬅️ Return to Dashboard</a>
</div>

</body>
</html>

<?php $conn->close(); ?>
