<?php
$host = "localhost";
$user = "root";    // change if you have another MySQL username
$pass = "";        // change if you have a MySQL password
$db   = "faculty_portal";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Fetch faculty profile (assuming single profile)
$sql = "SELECT * FROM faculty_profile WHERE id = 1";
$result = $conn->query($sql);
$profile = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Faculty Profile</title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f4f7f9;
    }
    .container {
      max-width: 1000px;
      margin: 40px auto;
      padding: 20px;
    }
    .profile-card {
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
      display: flex;
      flex-wrap: wrap;
      padding: 30px;
      align-items: center;
    }
    .profile-image {
      flex: 1 1 250px;
      text-align: center;
    }
    .profile-image img {
      width: 200px;
      height: 250px;
      border-radius: 8px;
      object-fit: cover;
      border: 2px;
    }
    .profile-details {
      flex: 2 1 500px;
      padding: 20px;
    }
    .profile-details h2 {
      margin-top: 0;
      color: #2c3e50;
    }
    .profile-details p {
      margin: 8px 0;
      color: #555;
      line-height: 1.6;
    }
    .label {
      font-weight: bold;
      color: #34495e;
    }
    .edit-btn {
      display: inline-block;
      margin-top: 20px;
      padding: 10px 20px;
      background-color: #1abc9c;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 14px;
      cursor: pointer;
      text-decoration: none;
    }
    .edit-btn:hover {
      background-color: #16a085;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="profile-card">
      <div class="profile-image">
        <img src="<?php echo $profile['image_url']; ?>" alt="Faculty Photo">
      </div>
      <div class="profile-details">
        <h2><?php echo $profile['name']; ?></h2>
        <p><span class="label">Department:</span> <?php echo $profile['department']; ?></p>
        <p><span class="label">Designation:</span> <?php echo $profile['designation']; ?></p>
        <p><span class="label">Email:</span> <?php echo $profile['email']; ?></p>
        <p><span class="label">Phone:</span> <?php echo $profile['phone']; ?></p>
        <p><span class="label">Research Interests:</span> <?php echo $profile['research']; ?></p>
        <a href="edit_profile.php" class="edit-btn">Edit Profile</a>
      </div>
    </div>
  </div>
</body>
</html>
