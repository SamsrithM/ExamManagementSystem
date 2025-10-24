<?php
session_start();

// DB connection settings
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "faculty_data";
$faculty_details_db = "new_registration_data";

// Connect to faculty login database
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Connect to faculty details database
$faculty_conn = new mysqli($db_host, $db_user, $db_pass, $faculty_details_db);
if ($faculty_conn->connect_error) {
    die("Faculty details connection failed: " . $faculty_conn->connect_error);
}

// Get POST values
$input_user = isset($_POST['username']) ? trim($_POST['username']) : '';
$input_pass = isset($_POST['password']) ? $_POST['password'] : '';

// Basic check
if ($input_user === '' || $input_pass === '') {
    echo "<h2 style='text-align:center; color:red;'>Please enter both username and password.</h2>";
    exit;
}

// Prepare and execute (prevent SQL injection)
$stmt = $conn->prepare("SELECT password FROM faculty WHERE username = ?");
$stmt->bind_param("s", $input_user);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $stmt->bind_result($db_password);
    $stmt->fetch();

    // Compare hashed password
    if (password_verify($input_pass, $db_password)) {
        // Get faculty email from faculty details database
        $email_stmt = $faculty_conn->prepare("SELECT email FROM faculty_new_data WHERE email = ?");
        $email_stmt->bind_param("s", $input_user);
        $email_stmt->execute();
        $email_result = $email_stmt->get_result();
        
        if ($email_result->num_rows > 0) {
            $faculty_data = $email_result->fetch_assoc();
            $_SESSION['faculty_user'] = $faculty_data['email'];
            $_SESSION['faculty_username'] = $input_user;
        }
        
        $email_stmt->close();
        
        // Redirect to login success page
        header("Location: faculty_login_success.php");
        exit; // Important to stop further execution
    } else {
        header("Location: faculty_password_wrong.php");
        exit; // Important to stop further execution
    }
} else {
    header("Location: faculty_userid_failed.php");
    exit; // Important to stop further execution
}

$stmt->close();
$conn->close();
$faculty_conn->close();
?>
