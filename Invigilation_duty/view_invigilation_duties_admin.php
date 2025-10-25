<?php
// view_invigilation_duties.php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "room_allocation";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("<h2 style='color:red;'>Connection failed: " . $conn->connect_error . "</h2>");
}

$popupMessage = "";

// Handle delete request securely
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    if ($delete_id > 0) {
        $stmt = $conn->prepare("DELETE FROM faculty_assignments WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $popupMessage = "Faculty duty deleted successfully!";
        } else {
            $popupMessage = "Error deleting record: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch all assigned duties
$sql = "SELECT a.id, g.classroom_name, a.faculty_name, a.email_id, a.assigned_at
        FROM faculty_assignments a
        JOIN generated_classrooms g ON a.classroom_id = g.id
        ORDER BY g.classroom_name, a.faculty_name";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Invigilation Duties</title>
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f7fa;
    margin: 0;
    padding: 40px 20px;
}
h1 {
    text-align: center;
    color: #1a73e8;
    margin-bottom: 20px;
    font-size: 2rem;
}
.table-container {
    max-width: 950px;
    margin: 0 auto;
    background: #fff;
    padding: 20px 25px;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}
thead {
    background: #1a73e8;
    color: #fff;
}
thead tr:hover {
    background-color: #1a73e8 !important;
}
tr:hover {
    background-color: #f1f7ff;
}
th, td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}
.delete-btn {
    background-color: #e63946;
    color: white;
    border: none;
    padding: 8px 14px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
}
.delete-btn:hover {
    background-color: #c9182b;
}
.back-btn {
    display: inline-block;
    margin: 25px auto 0 auto;
    padding: 10px 20px;
    font-size: 1rem;
    background: #0066cc;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    display: block;
    width: max-content;
}
.back-btn:hover {
    background: #004c99;
}
.no-data {
    text-align: center;
    padding: 30px 0;
    font-size: 1.1rem;
    color: #555;
}
.popup {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0, 123, 255, 0.95);
    color: white;
    padding: 20px 40px;
    border-radius: 10px;
    text-align: center;
    font-size: 1.2rem;
    box-shadow: 0 6px 15px rgba(0,0,0,0.3);
    z-index: 9999;
    animation: fadeOut 2.5s forwards;
}
@keyframes fadeOut {
    0% { opacity: 1; }
    80% { opacity: 1; }
    100% { opacity: 0; visibility: hidden; }
}

/* Responsive Table */
@media(max-width: 768px){
    .table-container { padding: 15px; width: 100%; }
    table, thead, tbody, th, td, tr { display: block; }
    thead tr { display: none; }
    tr { margin-bottom: 15px; border-bottom: 1px solid #ddd; }
    td { padding-left: 50%; position: relative; text-align: left; }
    td::before {
        content: attr(data-label);
        position: absolute;
        left: 15px;
        top: 12px;
        font-weight: bold;
        color: #333;
    }
}
</style>
</head>
<body>

<h1>Invigilation Duties</h1>
<div class="table-container">
    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Classroom</th>
                    <th>Faculty Name</th>
                    <th>Email ID</th>
                    <th>Assigned At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $count = 1; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td data-label="#"><?= $count++ ?></td>
                        <td data-label="Classroom"><?= htmlspecialchars($row['classroom_name']) ?></td>
                        <td data-label="Faculty Name"><?= htmlspecialchars($row['faculty_name']) ?></td>
                        <td data-label="Email ID"><?= htmlspecialchars($row['email_id']) ?></td>
                        <td data-label="Assigned At"><?= htmlspecialchars($row['assigned_at']) ?></td>
                        <td data-label="Action">
                            <a href="?delete_id=<?= $row['id'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this duty?');">🗑️ Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="http://localhost/Exam_Management_System/Admin/admin_front_page.php" class="back-btn">← Return to Dashboard</a>
    <?php else: ?>
        <div class="no-data">No invigilation duties have been assigned yet.</div>
        <a href="http://localhost/Exam_Management_System/Admin/admin_front_page.php" class="back-btn">← Return to Dashboard</a>
    <?php endif; ?>
</div>

<?php if (!empty($popupMessage)): ?>
<div class="popup"><?= htmlspecialchars($popupMessage) ?></div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const popup = document.querySelector('.popup');
    if (popup) popup.addEventListener('animationend', () => popup.remove());
});
</script>
<?php endif; ?>

</body>
</html>

<?php $conn->close(); ?>
