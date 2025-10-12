<?php
// admin_login.php

// DB connection settings — update if your MySQL root/password differ
$db_host = "localhost";
$db_user = "root";
$db_pass = "";            // usually blank on XAMPP by default
$db_name = "iiitdm_admin_db4";

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Only handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_user = isset($_POST['username']) ? trim($_POST['username']) : '';
    $input_pass = isset($_POST['password']) ? $_POST['password'] : '';

    if ($input_user === '' || $input_pass === '') {
        echo "<h3 style='text-align:center; color:#c0392b;'>Please enter both username and password.</h3>";
        $conn->close();
        exit;
    }

    // Prepare statement to safely fetch password for this username
    $stmt = $conn->prepare("SELECT admin_password FROM admin_users4 WHERE admin_username = ?");
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
            // EXACT message requested
            echo "<h2 style='color:green; text-align:center;'>Successfully logged in</h2>";
            echo "<p style='text-align:center;'>Welcome, <strong>" . htmlspecialchars($input_user) . "</strong></p>";
            // Optionally redirect:
            // header("Location: admin_dashboard.php");
            // exit;
        } else {
            echo "<h3 style='text-align:center; color:#c0392b;'>Incorrect password ❌</h3>";
            echo "<p style='text-align:center;'><a href='admin_login.html'>Try again</a></p>";
        }
    } else {
        echo "<h3 style='text-align:center; color:#c0392b;'>Username not found ❌</h3>";
        echo "<p style='text-align:center;'><a href='admin_login.html'>Try again</a></p>";
    }

    $stmt->close();
} else {
    echo "<h3 style='text-align:center;'>Please submit the form.</h3>";
}

$conn->close();
?>
