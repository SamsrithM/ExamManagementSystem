<?php
$host = "localhost";
$user = "root";    // change if you have another MySQL username
$pass = "";        // change if you have a MySQL password
$db   = "faculty_portal";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM faculty_profile WHERE id = 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  $profile = $result->fetch_assoc();
  echo json_encode(["status" => "success", "profile" => $profile]);
} else {
  echo json_encode(["status" => "error", "message" => "Profile not found"]);
}

$conn->close();
?>
