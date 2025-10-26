<?php
session_start();

// Redirect if admin not logged in
if (!isset($_SESSION['admin_user'])) {
    header("Location: admin_login.php");
    exit;
}

// Database connection using Render environment variables
$db_host = getenv('DB_HOST') ?: 'mysql';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_ADMIN') ?: 'admin_data';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch admin details
$admin_email = $_SESSION['admin_user'];
$stmt = $conn->prepare("SELECT * FROM admin WHERE admin_username = ?");
$stmt->bind_param("s", $admin_email);
$stmt->execute();
$result = $stmt->get_result();
$admin_data = $result->fetch_assoc();
$stmt->close();
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
