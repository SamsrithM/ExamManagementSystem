<?php
// register.php

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "iiitdm_registration_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';

    if ($role === 'student') {
        // Collect student fields
        $first_name = $_POST['first-name-student'] ?? '';
        $last_name = $_POST['last-name-student'] ?? '';
        $gender = $_POST['gender-student'] ?? '';
        $dob = $_POST['dob-student'] ?? null;
        $batch = $_POST['batch'] ?? null;
        $department = $_POST['department'] ?? '';
        $roll_number = $_POST['roll-number'] ?? '';
        $email = $_POST['institute-email-student'] ?? '';
        $course = $_POST['course'] ?? '';
        $semester = $_POST['semester'] ?? null;
        $password = $_POST['password-student'] ?? '';

        // Simple validation
        if ($first_name && $last_name && $gender && $roll_number && $email && $password) {
            $stmt = $conn->prepare("INSERT INTO students 
                (first_name,last_name,gender,dob,batch,department,roll_number,institute_email,course,semester,password) 
                VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssissssis",
                $first_name, $last_name, $gender, $dob, $batch, $department, $roll_number, $email, $course, $semester, $password);
            
            if ($stmt->execute()) {
                echo "<h2 style='color:green; text-align:center;'>Student Successfully Registered ✅</h2>";
            } else {
                echo "<h2 style='color:red; text-align:center;'>Error: " . $stmt->error . "</h2>";
            }
            $stmt->close();
        } else {
            echo "<h2 style='color:red; text-align:center;'>Please fill all required fields.</h2>";
        }

    } elseif ($role === 'faculty') {
        // Collect faculty fields
        $first_name = $_POST['first-name-faculty'] ?? '';
        $last_name = $_POST['last-name-faculty'] ?? '';
        $gender = $_POST['gender-faculty'] ?? '';
        $email = $_POST['email-faculty'] ?? '';
        $department = $_POST['department'] ?? '';
        $designation = $_POST['designation'] ?? '';
        $password = $_POST['password-faculty'] ?? '';

        // Simple validation
        if ($first_name && $last_name && $gender && $email && $password) {
            $stmt = $conn->prepare("INSERT INTO faculty 
                (first_name,last_name,gender,email,department,designation,password) 
                VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssss", $first_name, $last_name, $gender, $email, $department, $designation, $password);
            
            if ($stmt->execute()) {
                echo "<h2 style='color:green; text-align:center;'>Faculty Successfully Registered ✅</h2>";
            } else {
                echo "<h2 style='color:red; text-align:center;'>Error: " . $stmt->error . "</h2>";
            }
            $stmt->close();
        } else {
            echo "<h2 style='color:red; text-align:center;'>Please fill all required fields.</h2>";
        }

    } else {
        echo "<h2 style='color:red; text-align:center;'>Please select a role.</h2>";
    }
}

$conn->close();
?>
