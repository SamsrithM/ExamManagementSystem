<?php
session_start();

if (!isset($_SESSION['faculty_user'])) {
    header("Location: faculty_login.php");
    exit;
}

$faculty_email = $_SESSION['faculty_user'];

// Database connection using environment variables
$db_host = getenv('DB_HOST') ?: 'mysql';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'new_registration_data';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    if ($_FILES['photo']['error'] === 0) {
        $file_name = $_FILES['photo']['name'];
        $file_tmp = $_FILES['photo']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed)) {
            $upload_dir = 'faculty_uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $safe_email = preg_replace("/[^a-zA-Z0-9]/", "_", $faculty_email);
            $new_file_name = $safe_email . '_' . time() . '.' . $file_ext;
            $target_file = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $target_file)) {
                $stmt = $conn->prepare("UPDATE faculty_new_data SET photo=? WHERE email=?");
                $stmt->bind_param("ss", $new_file_name, $faculty_email);
                $stmt->execute();
                $stmt->close();
                $conn->close();

                header("Location: faculty_view_profile.php");
                exit;
            } else {
                echo "<h3 style='color:red; text-align:center;'>❌ Failed to move uploaded file.</h3>";
            }
        } else {
            echo "<h3 style='color:red; text-align:center;'>❌ Invalid file type. Only JPG, JPEG, PNG, GIF allowed.</h3>";
        }
    } else {
        echo "<h3 style='color:red; text-align:center;'>❌ Error uploading file.</h3>";
    }
} else {
    echo "<h3 style='color:red; text-align:center;'>No file uploaded.</h3>";
}
?>
