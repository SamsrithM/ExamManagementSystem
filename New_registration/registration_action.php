<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

$env = getenv('RENDER') ? 'render' : 'local';

// DB connection
if ($env === 'local') {
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $db_name = getenv('DB_STUDENT') ?: 'student_management';

    $conn = new mysqli($host, $user, $pass, $db_name);
    if ($conn->connect_error) {
        die("<h2 style='color:red;'>Database connection failed: " . htmlspecialchars($conn->connect_error) . "</h2>");
    }
} else {
    $db_host = getenv('DB_HOST');
    $db_port = getenv('DB_PORT') ?: '5432';
    $db_user = getenv('DB_USER');
    $db_pass = getenv('DB_PASS');
    $db_name = getenv('DB_STUDENT') ?: 'student_management';

    $conn = pg_connect("host=$db_host port=$db_port dbname=$db_name user=$db_user password=$db_pass");
    if (!$conn) die("<h2 style='color:red;'>PostgreSQL connection failed</h2>");
}

// Handle POST submission
$message = $message_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $roll_no    = trim($_POST['roll_no'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm_pw = $_POST['confirm_password'] ?? '';

    $errors = [];
    if (!$name || !$email || !$roll_no || !$password || !$confirm_pw) $errors[] = "All fields are required.";
    if ($password !== $confirm_pw) $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        if ($env === 'local') {
            $stmt = $conn->prepare("SELECT id FROM students WHERE email=? OR roll_no=?");
            $stmt->bind_param("ss", $email, $roll_no);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) $errors[] = "Email or Roll Number already registered.";
            $stmt->close();
        } else {
            $res = pg_query_params($conn, "SELECT id FROM students WHERE email=$1 OR roll_no=$2", [$email, $roll_no]);
            if (pg_num_rows($res) > 0) $errors[] = "Email or Roll Number already registered.";
        }
    }

    if (empty($errors)) {
        $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
        if ($env === 'local') {
            $stmt = $conn->prepare("INSERT INTO students (name, email, roll_no, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $roll_no, $hashed_pw);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Registration successful! Please log in.";
                $stmt->close();
                $conn->close();
                header("Location: student_login.php");
                exit;
            } else $errors[] = "Error registering student: " . htmlspecialchars($stmt->error);
        } else {
            $res = pg_query_params($conn,
                "INSERT INTO students (name, email, roll_no, password) VALUES ($1, $2, $3, $4)",
                [$name, $email, $roll_no, $hashed_pw]
            );
            if ($res) {
                $_SESSION['success_message'] = "Registration successful! Please log in.";
                pg_close($conn);
                header("Location: student_login.php");
                exit;
            } else $errors[] = "Error registering student!";
        }
    }

    if (!empty($errors)) {
        $message = implode("<br>", $errors);
        $message_type = 'error';
    } else {
        $message = "Registration successful!";
        $message_type = 'success';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registration Status</title>
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #e0f7fa, #ffffff);
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
    flex-direction: column;
    box-sizing: border-box;
}
.message-box {
    max-width: 500px;
    width: 90%;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    font-weight: bold;
    font-size: 1.1rem;
    margin-bottom: 20px;
    word-wrap: break-word;
}
.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.error {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}
.back-btn {
    padding: 10px 20px;
    background-color: #1a73e8;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: bold;
    transition: 0.3s;
    display: inline-block;
}
.back-btn:hover {
    background-color: #155ab6;
}
a {
    color: inherit;
    text-decoration: underline;
}

/* Responsive adjustments */
@media (max-width: 600px) {
    .message-box {
        font-size: 1rem;
        padding: 15px;
    }
    .back-btn {
        width: 100%;
        text-align: center;
        padding: 12px;
        font-size: 1rem;
    }
}
</style>
</head>
<body>

<?php if(!empty($message)): ?>
    <div class="message-box <?php echo htmlspecialchars($message_type); ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<a class="back-btn" href="registration_page.php">&#8592; Back to Registration</a>

</body>
</html>
