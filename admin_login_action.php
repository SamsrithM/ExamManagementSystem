<?php
// admin_login.php

// DB connection settings — update if your MySQL root/password differ
$db_host = "localhost";
$db_user = "root";
$db_pass = "";            // usually blank on XAMPP by default
$db_name = "admin_data";

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
    $stmt = $conn->prepare("SELECT admin_password FROM admin WHERE admin_username = ?");
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
            session_start();
            $_SESSION['admin_user'] = $input_user;
            header("Location: admin_login_success.php");
            exit; // Important: stop executing further
        } else {
            header("Location: admin_password_wrong.php");
            exit; // Important: stop executing further
        }
    } else {
        header("Location: userid_failed.php");
        exit; // Important: stop executing further
    }

    $stmt->close();
} else {
    echo "<h3 style='text-align:center;'>Please submit the form.</h3>";
}

$conn->close();
?>
