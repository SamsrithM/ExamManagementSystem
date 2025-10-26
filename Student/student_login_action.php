<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

// --- Environment variables ---
$db_type = getenv('DB_TYPE') ?: 'mysql'; // "mysql" or "pgsql"
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_STUDENT_DATA') ?: 'student_data';

// --- Get POST input ---
$input_user = isset($_POST['username']) ? trim($_POST['username']) : '';
$input_pass = isset($_POST['password']) ? $_POST['password'] : '';

if ($input_user === '' || $input_pass === '') {
    echo "<h3 style='text-align:center; color:#c0392b;'>Please enter both Student ID and Password.</h3>";
    exit;
}

// --- MySQL Connection ---
if ($db_type === 'mysql') {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) die("<h3 style='text-align:center; color:#c0392b;'>MySQL connection failed: " . htmlspecialchars($conn->connect_error) . "</h3>");
    
    $stmt = $conn->prepare("SELECT student_password FROM students WHERE student_username = ?");
    $stmt->bind_param("s", $input_user);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($db_hashed_password);
        $stmt->fetch();
        if (password_verify($input_pass, $db_hashed_password)) {
            $_SESSION['roll_number'] = $input_user;
            header("Location: student_login_success.php");
            exit;
        } else {
            header("Location: password_wrong.php");
            exit;
        }
    } else {
        header("Location: studentid_wrong.php");
        exit;
    }
    $stmt->close();
    $conn->close();
}

// --- PostgreSQL Connection ---
elseif ($db_type === 'pgsql') {
    $conn_string = "host=$db_host dbname=$db_name user=$db_user password=$db_pass";
    $conn = pg_connect($conn_string);
    if (!$conn) die("<h3 style='text-align:center; color:#c0392b;'>PostgreSQL connection failed.</h3>");

    $result = pg_prepare($conn, "login_query", 'SELECT student_password FROM students WHERE student_username=$1');
    $result = pg_execute($conn, "login_query", array($input_user));

    if (pg_num_rows($result) === 1) {
        $row = pg_fetch_assoc($result);
        $db_hashed_password = $row['student_password'];
        if (password_verify($input_pass, $db_hashed_password)) {
            $_SESSION['roll_number'] = $input_user;
            header("Location: student_login_success.php");
            exit;
        } else {
            header("Location: password_wrong.php");
            exit;
        }
    } else {
        header("Location: studentid_wrong.php");
        exit;
    }

    pg_free_result($result);
    pg_close($conn);
}

else {
    die("<h3 style='text-align:center; color:#c0392b;'>Unsupported DB type: " . htmlspecialchars($db_type) . "</h3>");
}
?>
