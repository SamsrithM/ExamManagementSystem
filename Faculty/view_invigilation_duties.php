<?php
session_start();

if (!isset($_SESSION['faculty_user'])) {
    header("Location: faculty_login.php");
    exit;
}

$faculty_email = $_SESSION['faculty_user'];

// Database connection using environment variables
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'room_allocation';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("<h2 style='color:red;'>Database connection failed: " . $conn->connect_error . "</h2>");
}

// Fetch faculty name
$stmt_name = $conn->prepare("SELECT faculty_name FROM faculty_assignments WHERE email_id = ? LIMIT 1");
$stmt_name->bind_param("s", $faculty_email);
$stmt_name->execute();
$result_name = $stmt_name->get_result();
$faculty_name = "Faculty";
if ($result_name->num_rows > 0) {
    $row_name = $result_name->fetch_assoc();
    $faculty_name = htmlspecialchars($row_name['faculty_name']);
}
$stmt_name->close();

// Fetch assigned duties
$stmt_duties = $conn->prepare("
    SELECT id, classroom_id, classroom, faculty_name, assigned_at, status 
    FROM faculty_assignments 
    WHERE email_id = ? 
    AND status != 'cancelled'
");
$stmt_duties->bind_param("s", $faculty_email);
$stmt_duties->execute();
$result_duties = $stmt_duties->get_result();

// Fetch total hours done
$stmt_hours = $conn->prepare("SELECT SUM(hours_of_duty_done) as total_hours FROM faculty_duty_done WHERE email_id = ?");
$stmt_hours->bind_param("s", $faculty_email);
$stmt_hours->execute();
$res_hours = $stmt_hours->get_result();
$row_hours = $res_hours->fetch_assoc();
$total_hours = $row_hours['total_hours'] ?? 0;
$stmt_hours->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invigilation Duties - <?php echo $faculty_name; ?></title>
<style>
body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  margin: 0;
  padding: 0;
  background: #f5f7fa;
  color: #333;
}

.container {
  max-width: 1200px;
  margin: 50px auto;
  padding: 20px;
}

h1 {
  text-align: center;
  font-size: 2.2em;
  color: #0d47a1;
  margin-bottom: 10px;
}

.hours-display {
  text-align: center;
  font-size: 1.2em;
  margin-bottom: 25px;
  color: #0d47a1;
}

.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
}

.card {
  background: #fff;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 6px 18px rgba(0,0,0,0.1);
  transition: transform 0.3s, box-shadow 0.3s;
}

.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 25px rgba(0,0,0,0.15);
}

.card h2 {
  font-size: 1.2em;
  color: #0d47a1;
  margin-bottom: 10px;
}

.card p {
  margin: 6px 0;
  font-size: 0.95em;
  color: #333;
}

.card button {
  margin-top: 10px;
  padding: 8px 14px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: bold;
}

.ok-btn {
  background-color: #4CAF50;
  color: white;
}

.dashboard-btn {
  display: block;
  width: 200px;
  margin: 30px auto 0;
  padding: 10px 0;
  text-align: center;
  background: #0d47a1;
  color: #fff;
  font-weight: bold;
  text-decoration: none;
  border-radius: 8px;
  box-shadow: 0 5px 12px rgba(0,0,0,0.2);
  transition: 0.3s;
}

.dashboard-btn:hover {
  background: #08306b;
  transform: translateY(-2px);
}

.no-duty {
  text-align: center;
  font-size: 1.2em;
  margin-top: 25px;
  color: #0d47a1;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .cards {
    grid-template-columns: 1fr;
  }
  .card {
    text-align: center;
  }
  .card button {
    width: 100%;
    margin-top: 12px;
  }
}
</style>
</head>
<body>
<div class="container">
    <h1>Invigilation Duties for <?php echo $faculty_name; ?></h1>
    <div class="hours-display">Total Hours Done: <span id="total-hours"><?php echo $total_hours; ?></span></div>

    <?php if ($result_duties->num_rows > 0): ?>
        <div class="cards">
            <?php while($row = $result_duties->fetch_assoc()): ?>
                <div class="card" id="duty-<?php echo $row['id']; ?>">
                    <h2>Classroom: <?php echo htmlspecialchars($row['classroom']); ?></h2>
                    <p><strong>Assigned At:</strong> <?php echo htmlspecialchars($row['assigned_at']); ?></p>
                    <p><strong>Duty ID:</strong> <?php echo htmlspecialchars($row['id']); ?></p>
                    <p><strong>Status:</strong> 
                        <?php 
                            $status = htmlspecialchars($row['status']);
                            if ($status === 'confirmed') {
                                echo "<span style='color:green;font-weight:bold;'>Confirmed ✅</span>";
                            } elseif ($status === 'pending') {
                                echo "<span style='color:orange;font-weight:bold;'>Pending ⏳</span>";
                            }
                        ?>
                    </p>

                    <?php if ($status === 'pending'): ?>
                        <button class="ok-btn" onclick="markDone(<?php echo $row['id']; ?>)">OK</button>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="no-duty">No invigilation duties assigned yet.</div>
    <?php endif; ?>

    <a href="faculty_front_page.php" class="dashboard-btn">Return to Dashboard</a>
</div>

<script>
function updateHours(newHours) {
    document.getElementById('total-hours').innerText = newHours;
}

// Mark duty done
function markDone(dutyId) {
    if (!confirm("Confirm that this duty is done?")) return;

    fetch('update_duty.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ action:'ok', duty_id:dutyId })
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success) {
            alert("Assigned duty confirmed successfully!");
            const card = document.getElementById('duty-' + dutyId);
            card.style.backgroundColor = "#e0f7e9";
            card.innerHTML += `<p style="color:green;font-weight:bold;">Status: Confirmed ✅</p>`;
            card.querySelectorAll('button').forEach(btn => btn.disabled = true);
            updateHours(data.total_hours);
        } else alert(data.message);
    });
}
</script>
</body>
</html>

<?php
$stmt_duties->close();
$conn->close();
?>
