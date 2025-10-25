<?php
session_start(); // ✅ Start session at the very top

// Database connection
$db_host = "localhost";
$db_user = "root";
$db_pass = ""; // Default for XAMPP
$db_name = "admin_data";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
 
// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_user = isset($_POST['username']) ? trim($_POST['username']) : '';
    $input_pass = isset($_POST['password']) ? $_POST['password'] : '';

    if ($input_user === '' || $input_pass === '') {
        echo "<h3 style='text-align:center; color:#c0392b;'>Please enter both username and password.</h3>";
        $conn->close();
        exit;
    }

    // Prepare SQL safely
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

        // Compare passwords (plain text here; can use password_hash later)
        if ($input_pass === $db_password) {
            // ✅ Store username in session for later access (like profile)
            $_SESSION['admin_user'] = $input_user;

            header("Location: admin_login_success.php");
            exit;
        } else {
            header("Location: admin_password_wrong.php");
            exit;
        }
    } else {
        header("Location: userid_failed.php");
        exit;
    }

    $stmt->close();
} else {
    echo "<h3 style='text-align:center;'>Please submit the form.</h3>";
}

$conn->close();
?>
