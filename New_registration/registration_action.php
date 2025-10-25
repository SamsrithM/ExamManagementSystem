<?php
session_start();

// Database connections
$host = "localhost";
$user = "root";
$pass = "";

// Main database for detailed registration
$conn = new mysqli($host, $user, $pass, "new_registration_data");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Separate databases for login credentials
$student_db = new mysqli($host, $user, $pass, "student_data");
$faculty_db = new mysqli($host, $user, $pass, "faculty_data");

// Initialize message variables
$message = '';
$message_type = ''; // 'success' or 'error'

// Check form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';

    if ($role === 'student') {
        // Collect student fields
        $first_name = trim($_POST['first-name-student'] ?? '');
        $last_name = trim($_POST['last-name-student'] ?? '');
        $gender = $_POST['gender-student'] ?? '';
        $dob = $_POST['dob-student'] ?? '';
        $batch = intval($_POST['batch'] ?? 0);
        $department = $_POST['student-department'] ?? '';
        $roll_number = trim($_POST['roll-number'] ?? '');
        $email = trim($_POST['institute-email-student'] ?? '');
        $course = $_POST['course'] ?? '';
        $semester = intval($_POST['semester'] ?? 0);
        $password = $_POST['password-student'] ?? '';
        $confirm_password = $_POST['confirm-password-student'] ?? '';

        // Password match check
        if ($password !== $confirm_password) {
            $message = "Passwords do not match. Please try again.";
            $message_type = 'error';
        }
        elseif ($first_name && $last_name && $gender && $roll_number && $email && $password) {
            $checkStmt = $conn->prepare("SELECT roll_number, institute_email FROM students_new_data WHERE roll_number = ? OR institute_email = ?");
            $checkStmt->bind_param("ss", $roll_number, $email);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows > 0) {
                $message = "Roll number or email already exists. Please use a different one.";
                $message_type = 'error';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmt1 = $conn->prepare("INSERT INTO students_new_data 
                    (first_name,last_name,gender,dob,batch,department,roll_number,institute_email,course,semester,password) 
                    VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                $stmt1->bind_param(
                    "ssssissssis",
                    $first_name, $last_name, $gender, $dob, $batch,
                    $department, $roll_number, $email, $course, $semester, $hashed_password
                );

                $stmt2 = $student_db->prepare("INSERT INTO students (student_username, student_password) VALUES (?, ?)");
                $stmt2->bind_param("ss", $roll_number, $hashed_password);

                if ($stmt1->execute() && $stmt2->execute()) {
                    $message = "Registration successful! You can now <a href='login.php'>login</a>.";
                    $message_type = 'success';
                } else {
                    $message = "Error: " . $stmt1->error . " / " . $stmt2->error;
                    $message_type = 'error';
                }

                $stmt1->close();
                $stmt2->close();
            }
            $checkStmt->close();
        } else {
            $message = "Please fill all required fields.";
            $message_type = 'error';
        }

    } elseif ($role === 'faculty') {
        $first_name = trim($_POST['first-name-faculty'] ?? '');
        $last_name = trim($_POST['last-name-faculty'] ?? '');
        $gender = $_POST['gender-faculty'] ?? '';
        $email = trim($_POST['email-faculty'] ?? '');
        $department = $_POST['faculty-department'] ?? '';
        $designation = $_POST['designation'] ?? '';
        $password = $_POST['password-faculty'] ?? '';
        $confirm_password = $_POST['confirm-password-faculty'] ?? '';

        // Password match check
        if ($password !== $confirm_password) {
            $message = "Passwords do not match. Please try again.";
            $message_type = 'error';
        }
        elseif ($first_name && $last_name && $gender && $email && $password) {
            $checkStmt = $conn->prepare("SELECT email FROM faculty_new_data WHERE email = ?");
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows > 0) {
                $message = "Email already exists. Please use a different one.";
                $message_type = 'error';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmt1 = $conn->prepare("INSERT INTO faculty_new_data 
                    (first_name,last_name,gender,email,department,designation,password) 
                    VALUES (?,?,?,?,?,?,?)");
                $stmt1->bind_param("sssssss", $first_name, $last_name, $gender, $email, $department, $designation, $hashed_password);

                $stmt2 = $faculty_db->prepare("INSERT INTO faculty (username, password) VALUES (?, ?)");
                $stmt2->bind_param("ss", $email, $hashed_password);

                if ($stmt1->execute() && $stmt2->execute()) {
                    $message = "Registration successful! You can now <a href='faculty_login.php'>login</a>.";
                    $message_type = 'success';
                } else {
                    $message = "Error: " . $stmt1->error . " / " . $stmt2->error;
                    $message_type = 'error';
                }

                $stmt1->close();
                $stmt2->close();
            }
            $checkStmt->close();
        } else {
            $message = "Please fill all required fields.";
            $message_type = 'error';
        }
    } else {
        $message = "Please select a role.";
        $message_type = 'error';
    }
}

// Close connections
$conn->close();
$student_db->close();
$faculty_db->close();
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
