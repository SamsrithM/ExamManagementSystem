<?php
// DB connection settings
$db_host = "localhost";
$db_user = "root";   // change if different
$db_pass = "";       // change if your MySQL root has a password
$db_name = "iiitdm_faculty_db2";

// Connect
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get POST values (simple sanitization)
$input_user = isset($_POST['username']) ? trim($_POST['username']) : '';
$input_pass = isset($_POST['password']) ? $_POST['password'] : '';

// Basic check
if ($input_user === '' || $input_pass === '') {
    echo "<h2 style='text-align:center; color:red;'>Please enter both username and password.</h2>";
    exit;
}

// Prepare and execute (prevent SQL injection)
$stmt = $conn->prepare("SELECT password FROM faculty_users2 WHERE username = ?");
$stmt->bind_param("s", $input_user);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $stmt->bind_result($db_password);
    $stmt->fetch();

    // Plain-text comparison (matches the INSERT above)
    if ($input_pass === $db_password) {
        echo "<h2 style='color:green; text-align:center;'>Login Successful ✅</h2>";
        echo "<p style='text-align:center;'>Welcome, <strong>" . htmlspecialchars($input_user) . "</strong></p>";
        // Optionally redirect to dashboard:
        // header("Location: dashboard.php");
        // exit;
    } else {
        echo "<h2 style='color:red; text-align:center;'>Incorrect password ❌</h2>";
        echo "<p style='text-align:center;'><a href='faculty_login.html'>Try again</a></p>";
    }
} else {
    echo "<h2 style='color:red; text-align:center;'>Username not found ❌</h2>";
    echo "<p style='text-align:center;'><a href='faculty_login.html'>Try again</a></p>";
}

$stmt->close();
$conn->close();
?>
