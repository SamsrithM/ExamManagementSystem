<?php
// student_login_new.php

// --- DB connection settings (update if your MySQL root has a password) ---
$db_host = "localhost";
$db_user = "root";
$db_pass = "";             // XAMPP default is usually blank
$db_name = "iiitdm_students_db3";

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get POST data safely
$input_user = isset($_POST['username']) ? trim($_POST['username']) : '';
$input_pass = isset($_POST['password']) ? $_POST['password'] : '';

// Basic empty check
if ($input_user === '' || $input_pass === '') {
    echo "<h3 style='text-align:center; color:#c0392b;'>Please enter both Student ID and Password.</h3>";
    exit;
}

// Prepare statement to fetch stored password for the username
$stmt = $conn->prepare("SELECT student_password FROM student_accounts3 WHERE student_username = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("s", $input_user);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $stmt->bind_result($db_password);
    $stmt->fetch();

    // Plain-text comparison (matches the INSERT above)
    if ($input_pass === $db_password) {
        // Login success
        echo "<h2 style='color:green; text-align:center;'>Login Successful ✅</h2>";
        echo "<p style='text-align:center;'>Welcome, <strong>" . htmlspecialchars($input_user) . "</strong></p>";

        // Optional: redirect to dashboard
        // header('Location: student_dashboard.php');
        // exit;
    } else {
        echo "<h3 style='color:#c0392b; text-align:center;'>Incorrect password ❌</h3>";
        echo "<p style='text-align:center;'><a href='student_login.html'>Try again</a></p>";
    }
} else {
    echo "<h3 style='color:#c0392b; text-align:center;'>Student ID not found ❌</h3>";
    echo "<p style='text-align:center;'><a href='student_login.html'>Try again</a></p>";
}

$stmt->close();
$conn->close();
?>
