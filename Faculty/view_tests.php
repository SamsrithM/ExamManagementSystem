<?php
session_start();

// Ensure faculty is logged in
if (!isset($_SESSION['faculty_user'])) {
    header("Location: faculty_login.php");
    exit;
}

$faculty_email = $_SESSION['faculty_user'];

// Detect environment
$is_render = getenv('RENDER') ? true : false;

// DB credentials
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: ($is_render ? 'postgres' : 'root');
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_TEST') ?: 'test_creation';

$results = [];

// Use PostgreSQL on Render
if ($is_render) {
    $conn = pg_connect("host=$host dbname=$db user=$user password=$pass");
    if (!$conn) die("<h2 style='color:red;'>PostgreSQL connection failed</h2>");

    // Handle deletion
    if (isset($_GET['delete_id'])) {
        $delete_id = (int)$_GET['delete_id'];
        pg_query_params($conn, "DELETE FROM questions WHERE test_id=$1", [$delete_id]);
        pg_query_params($conn, "DELETE FROM tests WHERE test_id=$1", [$delete_id]);
        header("Location: view_tests.php");
        exit;
    }

    $res = pg_query_params($conn, "SELECT * FROM tests WHERE created_by=$1 ORDER BY test_id DESC", [$faculty_email]);
    while ($row = pg_fetch_assoc($res)) $results[] = $row;
    pg_close($conn);

} else {
    // MySQL connection locally
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) die("<h2 style='color:red;'>MySQL connection failed</h2>");

    if (isset($_GET['delete_id'])) {
        $delete_id = (int)$_GET['delete_id'];
        $stmt_del_q = $conn->prepare("DELETE FROM questions WHERE test_id=?");
        $stmt_del_q->bind_param("i", $delete_id);
        $stmt_del_q->execute();
        $stmt_del_q->close();

        $stmt_del_t = $conn->prepare("DELETE FROM tests WHERE test_id=?");
        $stmt_del_t->bind_param("i", $delete_id);
        $stmt_del_t->execute();
        $stmt_del_t->close();

        header("Location: view_tests.php");
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM tests WHERE created_by=? ORDER BY test_id DESC");
    $stmt->bind_param("s", $faculty_email);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $results[] = $row;
    $stmt->close();
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View My Tests</title>
<style>
body {
    font-family: Arial, sans-serif;
    padding: 20px;
    background: #f4f7f9;
    margin:0;
}
h2 {
    text-align: center;
    color: #1abc9c;
    margin-bottom: 20px;
}
.table-wrapper {
    overflow-x:auto;
}
table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    min-width: 700px;
}
th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}
th {
    background-color: #1abc9c;
    color: white;
}
a.button {
    padding: 6px 12px;
    background: #1abc9c;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin-right:5px;
    display:inline-block;
    margin-bottom:5px;
}
a.button:hover { background: #159a85; }
a.delete {
    background: #e74c3c;
}
a.delete:hover { background: #c0392b; }
.return-btn {
    display:inline-block;
    margin-top:20px;
    padding:10px 20px;
    background:#1abc9c;
    color:white;
    text-decoration:none;
    border-radius:5px;
    transition:0.3s;
}
.return-btn:hover { background:#159a85; }

/* Responsive adjustments */
@media(max-width:768px){
    th, td { padding:10px; font-size:14px; }
    a.button { padding:5px 10px; font-size:13px; }
}
@media(max-width:480px){
    h2 { font-size:22px; }
    th, td { padding:8px; font-size:12px; }
    a.button { display:block; width:100%; margin-bottom:5px; text-align:center; }
}
</style>
</head>
<body>

<h2>Created Tests</h2>

<div class="table-wrapper">
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
</div>

<div style="text-align:center;">
    <a href="faculty_front_page.php" class="return-btn">⬅️ Return to Dashboard</a>
</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
