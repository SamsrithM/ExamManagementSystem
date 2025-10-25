<?php
session_start();

// Ensure a reset email is provided
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['user_type'])) {
    header("Location: forgot_password.php");
    exit;
}

$reset_email = $_SESSION['reset_email'];
$user_type   = $_SESSION['user_type']; // 'student' or 'faculty'

// Use environment variables for deployment
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db_student = getenv('STUDENT_DB') ?: 'student_registration_data';
$db_faculty = getenv('FACULTY_DB') ?: 'new_registration_data';

// Choose DB based on user type
$db_name = ($user_type === 'faculty') ? $db_faculty : $db_student;

// Connect to database
$conn = new mysqli($host, $user, $pass, $db_name);
if ($conn->connect_error) {
    die("<h2 style='color:red; text-align:center;'>Database connection failed.</h2>");
}

// Fetch user record
$table = ($user_type === 'faculty') ? 'faculty_new_data' : 'students';
$stmt = $conn->prepare("SELECT id, email FROM $table WHERE email = ?");
$stmt->bind_param("s", $reset_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("<h2 style='color:red; text-align:center;'>Invalid password reset request.</h2>");
}
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();
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

    <a class="back-login" href="/index.php">&#8592; Back to Login</a>
</div>

</body>
</html>
