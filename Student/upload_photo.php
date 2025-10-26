<?php
session_start();

if (!isset($_SESSION['roll_number'])) {
    header("Location: student_login.php");
    exit;
}

$roll_number = $_SESSION['roll_number'];

// --- DB environment variables ---
$db_type = getenv('DB_TYPE') ?: 'mysql'; // mysql or pgsql
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'new_registration_data';

// --- Check file upload ---
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

                // --- DB update ---
                if ($db_type === 'mysql') {
                    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
                    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

                    $stmt = $conn->prepare("UPDATE students_new_data SET photo=? WHERE roll_number=?");
                    $stmt->bind_param("ss", $new_file_name, $roll_number);
                    $stmt->execute();
                    $stmt->close();
                    $conn->close();

                } elseif ($db_type === 'pgsql') {
                    $conn_string = "host=$db_host dbname=$db_name user=$db_user password=$db_pass";
                    $conn = pg_connect($conn_string);
                    if (!$conn) die("PostgreSQL connection failed.");

                    $res = pg_prepare($conn, "update_photo", "UPDATE students_new_data SET photo=$1 WHERE roll_number=$2");
                    $res = pg_execute($conn, "update_photo", [$new_file_name, $roll_number]);
                    pg_close($conn);
                }

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
