<?php
session_start(); // start session at the very top

// --- DB connection settings via environment variables ---
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_STUDENT_DATA') ?: 'student_data';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("<h3 style='text-align:center; color:#c0392b;'>Connection failed: " . $conn->connect_error . "</h3>");
}

// Get POST input safely
$input_user = isset($_POST['username']) ? trim($_POST['username']) : '';
$input_pass = isset($_POST['password']) ? $_POST['password'] : '';

if ($input_user === '' || $input_pass === '') {
    echo "<h3 style='text-align:center; color:#c0392b;'>Please enter both Student ID and Password.</h3>";
    exit;
}

// Prepare and execute query
$stmt = $conn->prepare("SELECT student_password FROM students WHERE student_username = ?");
$stmt->bind_param("s", $input_user);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $stmt->bind_result($db_hashed_password);
    $stmt->fetch();

    if (password_verify($input_pass, $db_hashed_password)) {
        // Store the roll number (username) in session
        $_SESSION['roll_number'] = $input_user;

        // Redirect to login success page
        header("Location: student_login_success.php");
        exit;
    } else {
        // Wrong password
        header("Location: password_wrong.php");
        exit;
    }
} else {
    // Student ID not found
    header("Location: studentid_wrong.php");
    exit;
}

$stmt->close();
$conn->close();
?>
