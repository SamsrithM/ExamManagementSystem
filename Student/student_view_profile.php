<?php
session_start();

if (!isset($_SESSION['roll_number'])) {
    header("Location: student_login.php");
    exit;
}

$roll_number = $_SESSION['roll_number'];

// --- Environment detection ---
$env = getenv('RENDER') ? 'render' : 'local';

// --- Database credentials ---
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_port = getenv('DB_PORT') ?: ($env === 'local' ? '3306' : '5432');
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_STUDENT_DATA') ?: 'student_data';

$student = null;

// --- Connect to DB and fetch student ---
if ($env === 'local') {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) die("MySQL connection failed: " . htmlspecialchars($conn->connect_error));
    $conn->set_charset("utf8");

    $stmt = $conn->prepare("SELECT student_id, first_name, last_name, gender, dob, batch, department, institute_email, course, semester, photo FROM students_new_data WHERE roll_number = ?");
    $stmt->bind_param("s", $roll_number);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) $student = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
} else {
    $conn_string = "host=$db_host port=$db_port dbname=$db_name user=$db_user password=$db_pass";
    $conn = pg_connect($conn_string);
    if (!$conn) die("PostgreSQL connection failed.");

    $res = pg_prepare($conn, "student_query", "SELECT student_id, first_name, last_name, gender, dob, batch, department, institute_email, course, semester, photo FROM students_new_data WHERE roll_number=$1");
    $res = pg_execute($conn, "student_query", [$roll_number]);
    if (pg_num_rows($res) === 1) $student = pg_fetch_assoc($res);
    pg_free_result($res);
    pg_close($conn);
}

if (!$student) die("Student data not found.");

// --- Determine photo ---
$defaultPhoto = "https://imgs.search.brave.com/pkPyTQFTOVFQw7Hki6hg6cgY5FPZ3UzkpUMsnfiuznQ/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9jZG4u/dmVjdG9yc3RvY2su/Y29tL2kvNTAwcC80/MS85MC9hdmF0YXIt/ZGVmYXVsdC11c2Vy/LXByb2ZpbGUtaWNv/bi1zaW1wbGUtZmxh/dC12ZWN0b3ItNTcy/MzQxOTAuanBn";
$photoFile = !empty($student['photo']) && file_exists('uploads/'.$student['photo']) ? 'uploads/'.$student['photo'] : $defaultPhoto;
?>
<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Profile</title>
<style>
body { 
    font-family: Arial, sans-serif; 
    background: #e3f2fd; 
    margin: 0; 
    padding: 20px; 
}

.profile-card { 
    display: flex; 
    flex-wrap: wrap; 
    background: #fff; 
    border-radius: 12px; 
    box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
    overflow: hidden; 
    max-width: 900px; 
    margin: auto; 
}

.profile-image { 
    flex: 1 1 250px; 
    background: #f5f5f5; 
    text-align: center; 
    padding: 30px 20px; 
}

.profile-image img { 
    width: 180px; 
    height: 220px; 
    border-radius: 8px; 
    object-fit: cover; 
    border: 2px solid #ccc; 
}

.upload-btn { 
    margin-top: 15px; 
    padding: 8px 20px; 
    background: #1a73e8; 
    color: #fff; 
    border: none; 
    border-radius: 6px; 
    cursor: pointer; 
}

.upload-btn:hover { 
    background: #155ab6; 
}

.profile-details { 
    flex: 2 1 500px; 
    padding: 30px 25px; 
}

.profile-details h2 { 
    margin-top: 0; 
    color: #1a73e8; 
    margin-bottom: 20px; 
    font-size: 26px; 
}

.profile-details p { 
    font-size: 16px; 
    color: #333; 
    margin: 10px 0; 
}

.profile-details .label { 
    font-weight: bold; 
    color: #555; 
    width: 150px; 
    display: inline-block; 
}

.back-btn { 
    display: inline-block; 
    margin-top: 20px; 
    padding: 10px 25px; 
    background: #6c757d; 
    color: #fff; 
    border-radius: 6px; 
    text-decoration: none; 
}

.back-btn:hover { 
    background: #5a6268; 
}

@media (max-width: 768px) { 
    .profile-card { 
        flex-direction: column; 
    } 
    .profile-image, .profile-details { 
        flex: 1 1 100%; 
        text-align: center; 
    } 
    .profile-details .label { 
        display: block; 
        margin-bottom: 5px; 
    } 
}
</style>
</head>
<body>

<div class="profile-card">
    <div class="profile-image">
        <img src="<?php echo $photoFile; ?>" alt="Profile Photo" id="profilePreview">
        <form action="upload_photo.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="photo" accept="image/*" required onchange="previewPhoto(event)"><br>
            <button type="submit" class="upload-btn">Upload Photo</button>
        </form>
    </div>
    <div class="profile-details">
        <h2><?php echo htmlspecialchars($student['first_name'].' '.$student['last_name']); ?></h2>
        <p><span class="label">Gender:</span><?php echo ucfirst($student['gender']); ?></p>
        <p><span class="label">Date of Birth:</span><?php echo $student['dob']; ?></p>
        <p><span class="label">Batch:</span><?php echo $student['batch']; ?></p>
        <p><span class="label">Department:</span><?php echo $student['department']; ?></p>
        <p><span class="label">Roll Number:</span><?php echo $roll_number; ?></p>
        <p><span class="label">Email:</span><?php echo $student['institute_email']; ?></p>
        <p><span class="label">Course:</span><?php echo $student['course']; ?></p>
        <p><span class="label">Semester:</span><?php echo $student['semester']; ?></p>
        <a href="student_front_page.php" class="back-btn">← Back to Dashboard</a>
    </div>
</div>

<script>
function previewPhoto(event) {
    const reader = new FileReader();
    reader.onload = function(){
        const output = document.getElementById('profilePreview');
        output.src = reader.result;
    };
    reader.readAsDataURL(event.target.files[0]);
}
</script>

</body>
</html>
