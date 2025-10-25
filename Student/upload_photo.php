<?php
session_start();

if (!isset($_SESSION['roll_number'])) {
    header("Location: student_login.php");
    exit;
}
    
$host = "localhost";
$user = "root";
$pass = "";
$db = "new_registration_data";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$roll_number = $_SESSION['roll_number'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    if ($_FILES['photo']['error'] === 0) {
        $file_name = $_FILES['photo']['name'];
        $file_tmp = $_FILES['photo']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];

        if (in_array($file_ext, $allowed)) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $new_file_name = $roll_number.'_'.time().'.'.$file_ext;
            $target_file = $upload_dir.$new_file_name;

            if (move_uploaded_file($file_tmp, $target_file)) {
                $stmt = $conn->prepare("UPDATE students_new_data SET photo=? WHERE roll_number=?");
                $stmt->bind_param("ss", $new_file_name, $roll_number);
                $stmt->execute();
                $stmt->close();
                $conn->close();

                // Redirect back to profile page to show updated photo
                header("Location: student_view_profile.php");
                exit;
            } else {
                die("Failed to move uploaded file.");
            }
        } else {
            die("Invalid file type. Only JPG, JPEG, PNG, GIF allowed.");
        }
    } else {
        die("Error uploading file.");
    }
}
?>
