<?php
session_start();

// Detect environment
$is_render = getenv('RENDER') ? true : false; // Render sets env vars automatically

// DB credentials
$db_host = getenv('DB_HOST') ?: ($is_render ? 'your_postgres_host' : 'localhost');
$db_user = getenv('DB_USER') ?: ($is_render ? 'postgres' : 'root');
$db_pass = getenv('DB_PASS') ?: '';
$db_faculty = getenv('DB_FACULTY') ?: 'faculty_data';
$db_details = getenv('DB_NAME') ?: 'new_registration_data';

$input_user = isset($_POST['username']) ? trim($_POST['username']) : '';
$input_pass = isset($_POST['password']) ? $_POST['password'] : '';

if ($input_user === '' || $input_pass === '') {
    echo "<h2 style='text-align:center; color:red;'>Please enter both username and password.</h2>";
    exit;
}

if ($is_render) {
    // PostgreSQL connection
    $conn = pg_connect("host=$db_host dbname=$db_faculty user=$db_user password=$db_pass");
    $faculty_conn = pg_connect("host=$db_host dbname=$db_details user=$db_user password=$db_pass");

    if (!$conn || !$faculty_conn) die("Database connection failed.");

    // Fetch password
    $res = pg_prepare($conn, "login_query", "SELECT password FROM faculty WHERE username = $1");
    $res = pg_execute($conn, "login_query", [$input_user]);

    if (pg_num_rows($res) === 1) {
        $row = pg_fetch_assoc($res);
        if (password_verify($input_pass, $row['password'])) {
            $email_stmt = pg_prepare($faculty_conn, "email_query", "SELECT email FROM faculty_new_data WHERE email = $1");
            $email_res = pg_execute($faculty_conn, "email_query", [$input_user]);
            if (pg_num_rows($email_res) > 0) {
                $faculty_data = pg_fetch_assoc($email_res);
                $_SESSION['faculty_user'] = $faculty_data['email'];
                $_SESSION['faculty_username'] = $input_user;
            }
            header("Location: faculty_login_success.php");
            exit;
        } else {
            header("Location: faculty_password_wrong.php");
            exit;
        }
    } else {
        header("Location: faculty_userid_failed.php");
        exit;
    }

    pg_close($conn);
    pg_close($faculty_conn);

} else {
    // MySQL connection
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_faculty);
    $faculty_conn = new mysqli($db_host, $db_user, $db_pass, $db_details);

    if ($conn->connect_error || $faculty_conn->connect_error) die("Database connection failed.");

    $stmt = $conn->prepare("SELECT password FROM faculty WHERE username = ?");
    $stmt->bind_param("s", $input_user);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($db_password);
        $stmt->fetch();
        if (password_verify($input_pass, $db_password)) {
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
            header("Location: faculty_login_success.php");
            exit;
        } else {
            header("Location: faculty_password_wrong.php");
            exit;
        }
    } else {
        header("Location: faculty_userid_failed.php");
        exit;
    }

    $stmt->close();
    $conn->close();
    $faculty_conn->close();
}
?>
