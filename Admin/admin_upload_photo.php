<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if admin is logged in
if (!isset($_SESSION['admin_user'])) {
    header("Location: admin_login.php");
    exit;
}

// Render environment DB connection
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'admin_data';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$admin_username = $_SESSION['admin_user'];

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    if ($_FILES['photo']['error'] === 0) {
        $file_name = $_FILES['photo']['name'];
        $file_tmp = $_FILES['photo']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];

        if (in_array($file_ext, $allowed)) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $new_file_name = $admin_username.'_'.time().'.'.$file_ext;
            $target_file = $upload_dir.$new_file_name;

            if (move_uploaded_file($file_tmp, $target_file)) {
                $stmt = $conn->prepare("UPDATE admin SET photo=? WHERE admin_username=?");
                $stmt->bind_param("ss", $new_file_name, $admin_username);
                $stmt->execute();
                $stmt->close();

                header("Location: admin_profile_view.php");
                exit;
            } else {
                die("Failed to move uploaded file. Check folder permissions.");
            }
        } else {
            die("Invalid file type. Only JPG, JPEG, PNG, GIF allowed.");
        }
    } else {
        die("Error uploading file. Code: ".$_FILES['photo']['error']);
    }
}

// Fetch admin data
$stmt = $conn->prepare("SELECT admin_username, email, photo FROM admin WHERE admin_username=?");
$stmt->bind_param("s", $admin_username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
} else {
    die("Admin data not found.");
}

$conn->close();

// Default photo logic
$defaultPhoto = "https://imgs.search.brave.com/pkPyTQFTOVFQw7Hki6hg6cgY5FPZ3UzkpUMsnfiuznQ/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9jZG4u/dmVjdG9yc3RvY2su/Y29tL2kvNTAwcC80/MS85MC9hdmF0YXIt/ZGVmYXVsdC11c2Vy/LXByb2ZpbGUtaWNv/bi1zaW1wbGUtZmxh/dC12ZWN0b3ItNTcy/MzQxOTAuanBn";
$photoFile = !empty($admin['photo']) && file_exists('uploads/'.$admin['photo']) 
             ? 'uploads/'.$admin['photo'] 
             : $defaultPhoto;
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Profile</title>
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
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
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

  /* Responsive Styles */
  @media (max-width: 768px) {
    .profile-card {
      flex-direction: column;
    }

    .profile-image,
    .profile-details {
      flex: 1 1 100%;
      text-align: center;
      padding: 20px;
    }

    .profile-details .label {
      display: block;
      margin-bottom: 5px;
      width: auto;
    }

    .profile-details h2 {
      font-size: 22px;
    }

    .profile-details p {
      font-size: 15px;
    }

    .upload-btn {
      width: 100%;
    }

    .back-btn {
      width: 100%;
      padding: 12px;
      font-size: 0.95rem;
    }
  }

  @media (max-width: 480px) {
    .profile-image img {
      width: 150px;
      height: 180px;
    }

    .profile-details h2 {
      font-size: 20px;
    }

    .profile-details p {
      font-size: 14px;
    }

    .upload-btn {
      font-size: 0.95rem;
      padding: 10px 16px;
    }

    .back-btn {
      font-size: 0.9rem;
    }
  }
</style>
</head>
<body>

<div class="profile-card">
    <div class="profile-image">
        <img src="<?php echo $photoFile; ?>" alt="Profile Photo" id="profilePreview">
        <form action="admin_profile_view.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="photo" accept="image/*" required onchange="previewPhoto(event)"><br>
            <button type="submit" class="upload-btn">Upload Photo</button>
        </form>
    </div>
    <div class="profile-details">
        <h2><?php echo htmlspecialchars($admin['admin_username']); ?></h2>
        <p><span class="label">Email:</span><?php echo htmlspecialchars($admin['email']); ?></p>
        <a href="admin_front_page.php" class="back-btn">← Back to Dashboard</a>
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
