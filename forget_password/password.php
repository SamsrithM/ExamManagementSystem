<?php
session_start();
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Database connections
$host = "localhost";
$user = "root";
$pass = "";

$new_reg_conn = new mysqli($host, $user, $pass, "new_registration_data");
$student_conn = new mysqli($host, $user, $pass, "student_data");
$faculty_conn = new mysqli($host, $user, $pass, "faculty_data");
$admin_conn   = new mysqli($host, $user, $pass, "admin_data");

if ($new_reg_conn->connect_error || $student_conn->connect_error || $faculty_conn->connect_error || $admin_conn->connect_error) {
    die("Database connection failed.");
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
                $stmt = $new_reg_conn->prepare("SELECT institute_email FROM students_new_data WHERE roll_number=?");
                $stmt->bind_param("s", $roll_number);
                $stmt->execute();
                $stmt->bind_result($email_db);
                $stmt->fetch();
                $stmt->close();

                if (empty($email_db)) {
                    $message = "Roll number not registered.";
                } else {
                    $otp = rand(100000, 999999);
                    $_SESSION['otp'] = $otp;
                    $_SESSION['otp_role'] = $role;
                    $_SESSION['otp_roll'] = $roll_number;
                    $_SESSION['otp_email'] = $email_db;
                    $_SESSION['otp_time'] = time();

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
                if ($role === 'faculty') {
                    $stmt = $new_reg_conn->prepare("SELECT email FROM faculty_new_data WHERE email=?");
                } else { // admin
                    $stmt = $admin_conn->prepare("SELECT email FROM admin WHERE email=?");
                }
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows === 0) {
                    $message = "Email not registered for the selected role.";
                } else {
                    $otp = rand(100000, 999999);
                    $_SESSION['otp'] = $otp;
                    $_SESSION['otp_email'] = $email;
                    $_SESSION['otp_role'] = $role;
                    $_SESSION['otp_time'] = time();

                    if ($role !== 'admin') {
                        // Send OTP email for faculty
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
                $stmt->close();
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
            if ($role === 'student') {
                $roll_number = $_SESSION['otp_roll'];
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $student_conn->prepare("UPDATE students SET student_password=? WHERE student_username=?");
                $stmt->bind_param("ss", $hashed_password, $roll_number);
            } elseif ($role === 'faculty') {
                $email = $_SESSION['otp_email'];
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $faculty_conn->prepare("UPDATE faculty SET password=? WHERE username=?");
                $stmt->bind_param("ss", $hashed_password, $email);
            } elseif ($role === 'admin') {
                $email = $_SESSION['otp_email'];
                $stmt = $admin_conn->prepare("UPDATE admin SET admin_password=? WHERE email=?");
                $stmt->bind_param("ss", $new_password, $email);
            }

            if ($stmt && $stmt->execute()) {
                $message = "Password updated successfully! You can now <a href='login.php'>login</a>.";
                unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_roll'], $_SESSION['otp_time'], $_SESSION['otp_role']);
            } else {
                $message = "Error updating password: " . ($stmt ? $stmt->error : "Invalid role.");
                $show_otp_form = true;
            }
            if ($stmt) $stmt->close();
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

$student_conn->close();
$faculty_conn->close();
$admin_conn->close();
$new_reg_conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot/Reset Password</title>
<style>
body { 
    font-family: Arial, sans-serif; 
    background:#f4f7f9; 
    display:flex; 
    justify-content:center; 
    align-items:center; 
    height:100vh; 
    margin:0;
}
.container { 
    background:#fff; 
    padding:40px; 
    border-radius:12px; 
    box-shadow:0 8px 20px rgba(0,0,0,0.2); 
    width:100%; 
    max-width:400px; 
    text-align:center; 
}
h2 { 
    color:#2c3e50; 
    margin-bottom:20px; 
    font-size:24px;
}
input, select { 
    width:100%; 
    padding:12px; 
    margin-bottom:20px; 
    border-radius:8px; 
    border:1px solid #ccc; 
    font-size:16px; 
    box-sizing:border-box;
}
button { 
    width:100%; 
    padding:12px; 
    background-color:#1abc9c; 
    color:white; 
    font-size:16px; 
    border:none; 
    border-radius:8px; 
    cursor:pointer; 
    transition:0.3s; 
}
button:hover { 
    background-color:#16a085; 
}
.message { 
    margin-bottom:15px; 
    color:#e74c3c; 
    font-size:14px;
}
.back-login { 
    margin-top:15px; 
    display:inline-block; 
    color:#1abc9c; 
    text-decoration:none; 
    font-size:14px;
}
.back-login:hover { 
    text-decoration:underline; 
}
.timer { 
    font-weight:bold; 
    margin-bottom:15px; 
    color:#2c3e50; 
    font-size:14px;
}
@media(max-width:480px){
    .container { padding:30px; }
    h2 { font-size:20px; }
    input, select, button { font-size:14px; padding:10px; }
    .message, .back-login, .timer { font-size:12px; }
}
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

    <a class="back-login" href="http://localhost/Exam_Management_System/Ems_start/frontpage.php">&#8592; Back to Login</a>
</div>

</body>
</html>
