<?php
session_start();

// Make sure faculty is logged in
if (!isset($_SESSION['faculty_user'])) {
    header("Location: faculty_login.php");
    exit;
}

$faculty_email = $_SESSION['faculty_user'];

$host = "localhost";
$user = "root";
$pass = "";
$db   = "test_creation";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle deletion
if(isset($_GET['delete_id'])){
    $delete_id = (int)$_GET['delete_id'];
    // Delete questions first
    $conn->query("DELETE FROM questions WHERE test_id = $delete_id");
    // Delete test
    $conn->query("DELETE FROM tests WHERE test_id = $delete_id");
    header("Location: view_tests.php");
    exit;
}

// Fetch all tests created by logged-in faculty
$stmt = $conn->prepare("SELECT * FROM tests WHERE created_by = ? ORDER BY test_id DESC");
$stmt->bind_param("s", $faculty_email);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View My Tests</title>
<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f4f7f9; }
h2 { text-align: center; color: #1abc9c; margin-bottom: 20px; }
table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
th { background-color: #1abc9c; color: white; }
a.button { padding: 6px 12px; background: #1abc9c; color: white; text-decoration: none; border-radius: 5px; margin-right:5px; }
a.button:hover { background: #159a85; }
a.delete { background: #e74c3c; }
a.delete:hover { background: #c0392b; }
</style>
</head>
<body>

<h2>Created Tests</h2>

<table>
    <thead>
        <tr>
            <th>Test ID</th>
            <th>Branch</th>
            <th>Title</th>
            <th>Date</th>
            <th>Available From</th>
            <th>Duration</th>
            <th>Type</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['test_id']; ?></td>
            <td><?php echo $row['branch']; ?></td>
            <td><?php echo $row['test_title']; ?></td>
            <td><?php echo $row['test_date']; ?></td>
            <td><?php echo $row['available_from']; ?></td>
            <td><?php echo $row['duration']; ?></td>
            <td><?php echo $row['test_type']; ?></td>
            <td>
                <a class="button" href="view_questions.php?test_id=<?php echo $row['test_id']; ?>">View Questions</a>
                <a class="button delete" href="view_tests.php?delete_id=<?php echo $row['test_id']; ?>" onclick="return confirm('Are you sure you want to delete this test and all its questions?');">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="8" style="text-align:center;">No tests found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<div style="text-align:center; margin-top:20px;">
    <a href="faculty_front_page.php" style="padding:10px 20px; background:#1abc9c; color:white; text-decoration:none; border-radius:5px; transition:0.3s;">⬅️ Return to Dashboard</a>
</div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
