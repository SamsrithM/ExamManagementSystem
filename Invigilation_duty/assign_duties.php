<?php
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['admin_user'])) {
    header("Location: admin_login.php");
    exit;
}

$env = getenv('RENDER') ? 'render' : 'local';

// Database connection
if ($env === 'local') {
    $conn = new mysqli('localhost', 'root', '', 'room_allocation');
    if ($conn->connect_error) die("<h2 style='color:red; text-align:center;'>MySQL connection failed: " . $conn->connect_error . "</h2>");
} else {
    $db_host = getenv('DB_HOST');
    $db_port = getenv('DB_PORT') ?: '5432';
    $db_user = getenv('DB_USER');
    $db_pass = getenv('DB_PASS');
    $db_name = getenv('DB_ROOM') ?: 'room_allocation';
    $conn = pg_connect("host=$db_host port=$db_port dbname=$db_name user=$db_user password=$db_pass");
    if (!$conn) die("<h2 style='color:red; text-align:center;'>PostgreSQL connection failed</h2>");
}

// Fetch all classrooms
$all_classrooms = [];
if ($env === 'local') {
    $res = $conn->query("SELECT * FROM generated_classrooms ORDER BY id ASC");
    while ($row = $res->fetch_assoc()) $all_classrooms[] = $row;
} else {
    $res = pg_query($conn, "SELECT * FROM generated_classrooms ORDER BY id ASC");
    while ($row = pg_fetch_assoc($res)) $all_classrooms[] = $row;
}
?>

<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assign Duties - Classroom Management</title>
<style>
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    background: #f4f7fa; 
    margin: 0; 
    padding: 40px 20px; 
    display: flex; 
    justify-content: center; 
    flex-direction: column; 
    align-items: center; 
}
.container { 
    width: 100%; 
    max-width: 650px; 
    background: #fff; 
    padding: 30px 25px; 
    border-radius: 12px; 
    box-shadow: 0 8px 20px rgba(0,0,0,0.15); 
}
h1 { 
    text-align: center; 
    color: #1a73e8; 
    font-size: 2rem; 
    margin-bottom: 25px; 
}
.classroom-row { 
    display: flex; 
    align-items: center; 
    margin-bottom: 12px; 
    gap: 10px; 
    flex-wrap: wrap; 
}
button { 
    padding: 8px 14px; 
    font-weight: 600; 
    border: none; 
    border-radius: 6px; 
    cursor: pointer; 
    transition: all 0.3s ease; 
    font-size: 0.95rem; 
    height: 38px; 
}
.classroom-btn { 
    background: linear-gradient(135deg, #1a73e8, #155fc1); 
    color: #fff; 
    flex: 1; 
    min-width: 150px;
    text-align: center;
}
.assign-btn { 
    background: linear-gradient(135deg, #28a745, #1e7e34); 
    color: #fff; 
    min-width: 110px;
}
.delete-btn { 
    background: linear-gradient(135deg, #dc3545, #a71d2a); 
    color: #fff; 
    min-width: 110px;
}
.dashboard-btn { 
    background: linear-gradient(135deg, #1a73e8, #155fc1); 
    color: #fff; 
    width: 100%; 
    margin-top: 25px; 
    padding: 12px;
    font-size: 1rem;
}
.classroom-btn:hover { 
    background: linear-gradient(135deg, #155fc1, #1a5fb4); 
}
.assign-btn:hover { 
    background: linear-gradient(135deg, #1e7e34, #166227); 
}
.delete-btn:hover { 
    background: linear-gradient(135deg, #a71d2a, #7a1017); 
}
.dashboard-btn:hover { 
    background: linear-gradient(135deg, #155fc1, #1a5fb4); 
}
#assigned-faculty { 
    margin-top: 20px; 
    background: #e7f3ff; 
    padding: 15px; 
    border-radius: 8px; 
    display: none; 
    font-size: 0.95rem;
}
@media(max-width:480px){
    .container { padding: 20px 15px; }
    .classroom-row { flex-direction: column; align-items: stretch; }
    button { width: 100%; min-width: unset; }
    h1 { font-size: 1.5rem; margin-bottom: 20px; }
    #assigned-faculty { font-size: 0.9rem; }
}
</style>

</head>
<body>

<div class="container">
    <h1>Assign Duties</h1>
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="classroom-row" id="row-<?= htmlspecialchars($row['id']) ?>">
                <button class="classroom-btn">
                    <?= htmlspecialchars($row['classroom_name']) ?> 
                    (<?= htmlspecialchars($row['exam_date']) ?>, <?= htmlspecialchars($row['exam_time']) ?>)
                </button>
                <button class="assign-btn" 
                        onclick="assignDuty('<?= htmlspecialchars($row['id']) ?>', 
                                            '<?= htmlspecialchars($row['exam_date']) ?>', 
                                            '<?= htmlspecialchars($row['exam_time']) ?>')">
                    Assign Duty
                </button>
                <button class="delete-btn" onclick="deleteClassroom('<?= htmlspecialchars($row['id']) ?>')">Delete</button>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align:center; font-size:1.1rem; color:#555;">No classrooms found in the database.</p>
    <?php endif; ?>

    <div id="assigned-faculty"></div>

    <button class="dashboard-btn" onclick="window.location.href='Admin/admin_front_page.php'">
        ← Back to Dashboard
    </button>
</div>

<script>
function assignDuty(classroomId, slotDate, slotTime) {
    fetch('assign_faculty.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            classroom_id: classroomId,
            slot_date: slotDate,
            slot_time: slotTime
        })
    })
    .then(response => response.json())
    .then(data => {
        const display = document.getElementById('assigned-faculty');
        display.style.display = 'block';

        if (data.status === 'success' || data.status === 'already_assigned') {
            display.innerHTML = `<strong>Classroom:</strong> ${data.classroom}<br>
                                 <strong>Faculty Assigned:</strong> ${data.faculty_name} (${data.email_id})<br>
                                 <em>${data.message}</em>`;
        } else {
            alert(data.message);
            display.style.display = 'none';
        }
    })
    .catch(err => {
        console.error(err);
        alert("Error assigning duty!");
    });
}

function deleteClassroom(classroomId) {
    if (!confirm("Are you sure you want to delete this classroom?")) return;

    fetch('delete_classroom.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: classroomId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            document.getElementById('row-' + classroomId).remove();
        } else {
            alert(data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert("Error deleting classroom!");
    });
}
</script>

</body>
</html>
<?php $conn->close(); ?>
