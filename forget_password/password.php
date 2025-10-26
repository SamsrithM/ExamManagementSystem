<?php
session_start();
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Environment detection
$env = getenv('RENDER') ? 'render' : 'local';

// Database connections
if ($env === 'local') {
    // MySQL local
    $db_host = '127.0.0.1';
    $db_user = 'root';
    $db_pass = '';
    $db_name = getenv('DB_NAME') ?: 'new_registration_data';

    $new_reg_conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $student_conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $faculty_conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $admin_conn   = new mysqli($db_host, $db_user, $db_pass, $db_name);

    foreach ([$new_reg_conn, $student_conn, $faculty_conn, $admin_conn] as $conn) {
        if ($conn->connect_error) die("MySQL connection failed: " . $conn->connect_error);
        $conn->set_charset("utf8");
    }
} else {
    // PostgreSQL on Render
    $db_host = getenv('DB_HOST');
    $db_port = getenv('DB_PORT') ?: 5432;
    $db_user = getenv('DB_USER');
    $db_pass = getenv('DB_PASS');
    $db_name = getenv('DB_NAME') ?: 'new_registration_data';

    $new_reg_conn = pg_connect("host=$db_host port=$db_port dbname=$db_name user=$db_user password=$db_pass");
    $student_conn = pg_connect("host=$db_host port=$db_port dbname=$db_name user=$db_user password=$db_pass");
    $faculty_conn = pg_connect("host=$db_host port=$db_port dbname=$db_name user=$db_user password=$db_pass");
    $admin_conn   = pg_connect("host=$db_host port=$db_port dbname=$db_name user=$db_user password=$db_pass");

    if (!$new_reg_conn || !$student_conn || !$faculty_conn || !$admin_conn) die("PostgreSQL connection failed!");
}

$message = '';
$show_otp_form = false;
$otp_remaining = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';

    // Step 1: Send OTP
    if (isset($_POST['send_otp'])) {
        if ($role === 'student') {
            $roll_number = trim($_POST['roll_number'] ?? '');
            if (empty($roll_number)) {
                $message = "Please enter your roll number.";
            } else {
                if ($env === 'local') {
                    $stmt = $new_reg_conn->prepare("SELECT institute_email FROM students_new_data WHERE roll_number=?");
                    $stmt->bind_param("s", $roll_number);
                    $stmt->execute();
                    $stmt->bind_result($email_db);
                    $stmt->fetch();
                    $stmt->close();
                } else {
                    $res = pg_query_params($new_reg_conn, "SELECT institute_email FROM students_new_data WHERE roll_number=$1", [$roll_number]);
                    $row = pg_fetch_assoc($res);
                    $email_db = $row['institute_email'] ?? '';
                }

                if (empty($email_db)) {
                    $message = "Roll number not registered.";
                } else {
                    $otp = rand(100000, 999999);
                    $_SESSION['otp'] = $otp;
                    $_SESSION['otp_role'] = $role;
                    $_SESSION['otp_roll'] = $roll_number;
                    $_SESSION['otp_email'] = $email_db;
                    $_SESSION['otp_time'] = time();

                    // Send OTP email
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'mahammadirfan4242@gmail.com';
                        $mail->Password   = 'iwwa iyel vzcs ifnu'; // Gmail App Password
                        $mail->SMTPSecure = 'tls';
                        $mail->Port       = 587;

                        $mail->setFrom('mahammadirfan4242@gmail.com', 'Exam Management System');
                        $mail->addAddress($email_db);

                        $mail->isHTML(true);
                        $mail->Subject = 'Password Reset OTP';
                        $mail->Body    = "Your OTP for password reset is: <b>$otp</b>. It is valid for 10 minutes.";

                        $mail->send();
                        $message = "OTP has been sent to your email.";
                        $show_otp_form = true;
                        $otp_remaining = 600;
                    } catch (Exception $e) {
                        $message = "Could not send OTP. Mailer Error: {$mail->ErrorInfo}";
                    }
                }
            }
        } elseif ($role === 'faculty' || $role === 'admin') {
            $email = trim($_POST['email'] ?? '');
            if (empty($email)) {
                $message = "Please enter your registered email.";
            } else {
                if ($env === 'local') {
                    if ($role === 'faculty') {
                        $stmt = $new_reg_conn->prepare("SELECT email FROM faculty_new_data WHERE email=?");
                    } else {
                        $stmt = $admin_conn->prepare("SELECT email FROM admin WHERE email=?");
                    }
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $stmt->store_result();
                    $exists = $stmt->num_rows > 0;
                    $stmt->close();
                } else {
                    if ($role === 'faculty') {
                        $res = pg_query_params($new_reg_conn, "SELECT email FROM faculty_new_data WHERE email=$1", [$email]);
                    } else {
                        $res = pg_query_params($admin_conn, "SELECT email FROM admin WHERE email=$1", [$email]);
                    }
                    $exists = pg_num_rows($res) > 0;
                }

                if (!$exists) {
                    $message = "Email not registered for the selected role.";
                } else {
                    $otp = rand(100000, 999999);
                    $_SESSION['otp'] = $otp;
                    $_SESSION['otp_email'] = $email;
                    $_SESSION['otp_role'] = $role;
                    $_SESSION['otp_time'] = time();

                    if ($role !== 'admin') {
                        $mail = new PHPMailer(true);
                        try {
                            $mail->isSMTP();
                            $mail->Host       = 'smtp.gmail.com';
                            $mail->SMTPAuth   = true;
                            $mail->Username   = 'mahammadirfan4242@gmail.com';
                            $mail->Password   = 'iwwa iyel vzcs ifnu';
                            $mail->SMTPSecure = 'tls';
                            $mail->Port       = 587;

                            $mail->setFrom('mahammadirfan4242@gmail.com', 'Exam Management System');
                            $mail->addAddress($email);

                            $mail->isHTML(true);
                            $mail->Subject = 'Password Reset OTP';
                            $mail->Body    = "Your OTP for password reset is: <b>$otp</b>. It is valid for 10 minutes.";

                            $mail->send();
                            $message = "OTP has been sent to your email.";
                        } catch (Exception $e) {
                            $message = "Could not send OTP. Mailer Error: {$mail->ErrorInfo}";
                        }
                    } else {
                        $message = "OTP generated. Please enter OTP and new password.";
                    }
                    $show_otp_form = true;
                    $otp_remaining = 600;
                }
            }
        } else {
            $message = "Please select a valid role.";
        }
    }

    // Step 2: Validate OTP & Reset Password
    if (isset($_POST['reset_password'])) {
        $otp_input = trim($_POST['otp'] ?? '');
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        $role = $_SESSION['otp_role'] ?? '';

        if (empty($otp_input) || empty($new_password) || empty($confirm_password)) {
            $message = "Please fill all fields.";
            $show_otp_form = true;
        } elseif ($new_password !== $confirm_password) {
            $message = "Passwords do not match.";
            $show_otp_form = true;
        } elseif (!isset($_SESSION['otp'], $_SESSION['otp_time'])) {
            $message = "Session expired. Please request OTP again.";
        } elseif (time() - $_SESSION['otp_time'] > 600) {
            $message = "OTP expired. Please request a new one.";
            unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_roll'], $_SESSION['otp_time'], $_SESSION['otp_role']);
        } elseif ($_SESSION['otp'] != $otp_input) {
            $message = "Invalid OTP. Please try again.";
            $show_otp_form = true;
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            if ($role === 'student') {
                $roll_number = $_SESSION['otp_roll'];
                if ($env === 'local') {
                    $stmt = $student_conn->prepare("UPDATE students SET student_password=? WHERE student_username=?");
                    $stmt->bind_param("ss", $hashed_password, $roll_number);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    pg_query_params($student_conn, "UPDATE students SET student_password=$1 WHERE student_username=$2", [$hashed_password, $roll_number]);
                }
            } elseif ($role === 'faculty') {
                $email = $_SESSION['otp_email'];
                if ($env === 'local') {
                    $stmt = $faculty_conn->prepare("UPDATE faculty SET password=? WHERE username=?");
                    $stmt->bind_param("ss", $hashed_password, $email);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    pg_query_params($faculty_conn, "UPDATE faculty SET password=$1 WHERE username=$2", [$hashed_password, $email]);
                }
            } elseif ($role === 'admin') {
                $email = $_SESSION['otp_email'];
                if ($env === 'local') {
                    $stmt = $admin_conn->prepare("UPDATE admin SET admin_password=? WHERE email=?");
                    $stmt->bind_param("ss", $hashed_password, $email);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    pg_query_params($admin_conn, "UPDATE admin SET admin_password=$1 WHERE email=$2", [$hashed_password, $email]);
                }
            }

            $message = "Password updated successfully! You can now <a href='login.php'>login</a>.";
            unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_roll'], $_SESSION['otp_time'], $_SESSION['otp_role']);
        }
    }
}

// OTP remaining time
if ($show_otp_form && isset($_SESSION['otp_time'])) {
    $otp_remaining = 600 - (time() - $_SESSION['otp_time']);
    if ($otp_remaining <= 0) {
        $otp_remaining = 0;
        unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_roll'], $_SESSION['otp_time'], $_SESSION['otp_role']);
        $message = "OTP expired. Please request a new one.";
        $show_otp_form = false;
    }
}

// Close connections
if ($env === 'local') {
    $student_conn->close();
    $faculty_conn->close();
    $admin_conn->close();
    $new_reg_conn->close();
} else {
    pg_close($student_conn);
    pg_close($faculty_conn);
    pg_close($admin_conn);
    pg_close($new_reg_conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot/Reset Password</title>
<style>
body { font-family: Arial, sans-serif; background:#f4f7f9; display:flex; justify-content:center; align-items:center; height:100vh; }
.container { background:#fff; padding:40px; border-radius:12px; box-shadow:0 8px 20px rgba(0,0,0,0.2); width:100%; max-width:400px; text-align:center; }
h2 { color:#2c3e50; margin-bottom:20px; }
input, select { width:100%; padding:12px; margin-bottom:20px; border-radius:8px; border:1px solid #ccc; font-size:16px; }
button { width:100%; padding:12px; background-color:#1abc9c; color:white; font-size:16px; border:none; border-radius:8px; cursor:pointer; transition:0.3s; }
button:hover { background-color:#16a085; }
.message { margin-bottom:15px; color:#e74c3c; }
.back-login { margin-top:15px; display:inline-block; color:#1abc9c; text-decoration:none; }
.back-login:hover { text-decoration:underline; }
.timer { font-weight:bold; margin-bottom:15px; color:#2c3e50; }
</style>
</head>
<body>

<div class="container">
    <h2>Forgot/Reset Password</h2>
    <?php if (!empty($message)): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if ($show_otp_form): ?>
        <div class="timer" id="timer"></div>
        <form method="POST" action="">
            <input type="text" name="otp" placeholder="Enter OTP" required>
            <input type="password" name="new_password" placeholder="New Password" required minlength="6">
            <input type="password" name="confirm_password" placeholder="Confirm Password" required minlength="6">
            <button type="submit" name="reset_password">Reset Password</button>
        </form>
        <script>
        let remaining = <?php echo $otp_remaining; ?>;
        const timerEl = document.getElementById('timer');
        function startTimer() {
            const interval = setInterval(() => {
                if (remaining <= 0) {
                    clearInterval(interval);
                    timerEl.innerHTML = "OTP expired. Please request a new one.";
                    document.querySelector('form').querySelectorAll('input, button').forEach(el => el.disabled = true);
                } else {
                    let minutes = Math.floor(remaining / 60);
                    let seconds = remaining % 60;
                    timerEl.innerHTML = `OTP expires in: ${minutes.toString().padStart(2,'0')}:${seconds.toString().padStart(2,'0')}`;
                    remaining--;
                }
            }, 1000);
        }
        startTimer();
        </script>
    <?php else: ?>
        <form method="POST" action="">
            <select name="role" id="role" required onchange="toggleFields()">
                <option value="">Select Role</option>
                <option value="student">Student</option>
                <option value="faculty">Faculty</option>
                <option value="admin">Admin</option>
            </select>
            <div id="student_field" style="display:none;">
                <input type="text" name="roll_number" placeholder="Enter your Roll Number">
            </div>
            <div id="email_field" style="display:none;">
                <input type="email" name="email" placeholder="Enter your registered email">
            </div>
            <button type="submit" name="send_otp">Send OTP</button>
        </form>
        <script>
        function toggleFields() {
            const role = document.getElementById('role').value;
            document.getElementById('student_field').style.display = (role === 'student') ? 'block' : 'none';
            document.getElementById('email_field').style.display = (role === 'faculty' || role === 'admin') ? 'block' : 'none';
        }
        toggleFields();
        </script>
    <?php endif; ?>

    <a class="back-login" href="/index.php">&#8592; Back to Login</a>
</div>

</body>
</html>

