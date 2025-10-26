<?php
session_start();

if (!isset($_SESSION['faculty_user'])) {
    header("Location: faculty_login.php");
    exit;
}

// DB connection using environment variables
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name   = getenv('DB_NAME') ?: 'new_registration_data';

// Connect to faculty details database
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$faculty_email = $_SESSION['faculty_user'];

// Fetch faculty data
$stmt = $conn->prepare("SELECT first_name, last_name, gender, email, department, designation, photo FROM faculty_new_data WHERE email = ?");
$stmt->bind_param("s", $faculty_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $faculty = $result->fetch_assoc();
} else {
    die("<h2 style='text-align:center; color:red;'>Faculty data not found for: $faculty_email</h2>");
}

$conn->close();

// Photo path
$defaultPhoto = "https://imgs.search.brave.com/pkPyTQFTOVFQw7Hki6hg6cgY5FPZ3UzkpUMsnfiuznQ/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9jZG4u/dmVjdG9yc3RvY2su/Y29tL2kvNTAwcC80/MS85MC9hdmF0YXIt/ZGVmYXVsdC11c2Vy/LXByb2ZpbGUtaWNv/bi1zaW1wbGUtZmxh/dC12ZWN0b3ItNTcy/MzQxOTAuanBn";

$photoPath = (!empty($faculty['photo']) && file_exists("faculty_uploads/" . $faculty['photo']))
    ? "faculty_uploads/" . htmlspecialchars($faculty['photo'])
    : $defaultPhoto;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Faculty Profile</title>
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
  transition: background 0.3s ease; 
}

.back-btn:hover { 
  background: #5a6268; 
}

.upload-form { 
  margin-top: 15px; 
}

.upload-form input[type="file"] { 
  margin-top: 10px; 
  padding: 5px; 
}

.upload-form button { 
  margin-top: 10px; 
  padding: 8px 15px; 
  background: #1a73e8; 
  color: #fff; 
  border: none; 
  border-radius: 6px; 
  cursor: pointer; 
  transition: background 0.3s ease; 
}

.upload-form button:hover { 
  background: #0b5ed7; 
}

@media (max-width:768px) {
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
            <img src="<?php echo $photoPath; ?>" alt="Profile Photo">
            <form class="upload-form" action="upload_faculty_photo.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="photo" accept="image/*" required>
                <button type="submit">Upload Image</button>
            </form>
        </div>

        <div class="profile-details">
            <h2><?php echo htmlspecialchars($faculty['first_name'].' '.$faculty['last_name']); ?></h2>
            <p><span class="label">Gender:</span><?php echo ucfirst($faculty['gender']); ?></p>
            <p><span class="label">Email:</span><?php echo htmlspecialchars($faculty['email']); ?></p>
            <p><span class="label">Department:</span><?php echo htmlspecialchars($faculty['department']); ?></p>
            <p><span class="label">Designation:</span><?php echo htmlspecialchars($faculty['designation']); ?></p>
            <a href="faculty_front_page.php" class="back-btn">← Back to Dashboard</a>
        </div>
    </div>

    </body>
    </html>
