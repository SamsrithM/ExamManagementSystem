<?php
// faculty_list.php

// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "new_registration_data"; // ✅ Correct database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("<h2 style='color:red;'>Connection failed: " . $conn->connect_error . "</h2>");
}
    
$db_error = "";
$faculty = [];

// ✅ Fetch faculty data
$sql = "SELECT faculty_id, first_name, last_name, gender, email, department, designation FROM faculty_new_data";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $faculty[] = $row;
    }
} elseif ($conn->error) {
    $db_error = $conn->error;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Faculty List</title>
<?php
// faculty_list.php

// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "new_registration_data"; // ✅ Correct database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("<h2 style='color:red;'>Connection failed: " . $conn->connect_error . "</h2>");
}

$db_error = "";
$faculty = [];

// ✅ Fetch faculty data
$sql = "SELECT faculty_id, first_name, last_name, gender, email, department, designation FROM faculty_new_data";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $faculty[] = $row;
    }
} elseif ($conn->error) {
    $db_error = $conn->error;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Faculty List</title>
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f8f9fa;
        margin: 0;
        padding: 30px;
    }
    h1 {
        text-align: center;
        color: #003366;
        margin-bottom: 10px;
    }
    h1:hover {
        color: #003366;  /* Same as normal color */
    }
    .search-container {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 15px;
    }
    .search-input {
        padding: 10px 14px;
        width: 250px;
        border: 1px solid #ccc;
        border-radius: 25px;
        outline: none;
        font-size: 15px;
        transition: all 0.3s ease;
    }
    .search-input:focus {
        border-color: #003366;
        box-shadow: 0 0 5px rgba(0, 51, 102, 0.4);
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    /* Fix table header color */
    thead th {
        color: white;           /* Keep header text white */
        background-color: #003366; /* Keep header background dark blue */
    }

    /* Prevent header color change on hover */
    thead th:hover {
        color: white;
        background-color: #003366;
    }

    
    th, td {
        padding: 12px 15px;
        text-align: left;
    }
    tr:nth-child(even) {
        background: #f2f6ff;
    }
    tr:hover {
        background: #e6f0ff;
    }
    .no-data {
        text-align: center;
        padding: 40px;
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        margin-top: 20px;
    }
    .no-data h3 {
        color: #856404;
        margin-bottom: 15px;
    }
    .no-data p, .no-data ol {
        color: #856404;
    }
    .register-link {
        background: #0066cc;
        color: white;
        padding: 10px 20px;
        text-decoration: none;
        border-radius: 5px;
    }
</style>
</head>
<body>

<div id="facultyListSection" class="faculty-list">
    <h1>Faculty List</h1>

    <?php if (empty($db_error) && !empty($faculty)): ?>
        <!-- ✅ Search Bar (Top-Right) -->
        <div class="search-container">
            <input type="text" id="searchInput" class="search-input" placeholder="🔍 Tap to search...">
        </div>

        <div style="overflow-x: auto; margin-top: 10px;">
            <table id="facultyTable">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Faculty ID</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Designation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($faculty as $index => $faculty_member): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($faculty_member['faculty_id']); ?></td>
                            <td style="font-weight: bold; color: #003366;">
                                <?php echo htmlspecialchars($faculty_member['first_name'] . ' ' . $faculty_member['last_name']); ?>
                            </td>
                            <td><?php echo ucfirst(htmlspecialchars($faculty_member['gender'])); ?></td>
                            <td><?php echo htmlspecialchars($faculty_member['email']); ?></td>
                            <td><?php echo htmlspecialchars($faculty_member['department']); ?></td>
                            <td><?php echo htmlspecialchars($faculty_member['designation']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="text-align:center; margin-top:20px;">
                <a href="admin_front_page.php" 
                style="display:inline-block; background:#0066cc; color:white; padding:10px 20px; border-radius:6px; text-decoration:none;">
                ← Return to Dashboard
                </a>
            </div>
        </div>

        <script>
            // ✅ Live Search Filter
            document.getElementById('searchInput').addEventListener('keyup', function() {
                let filter = this.value.toLowerCase();
                let rows = document.querySelectorAll('#facultyTable tbody tr');

                rows.forEach(row => {
                    let text = row.innerText.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            });
        </script>

    <?php else: ?>
        <div class="no-data">
            <h3>⚠️ No Faculty Data Found</h3>
            <p>No faculty members have been registered yet.</p>
            <p style="font-size: 14px;">To add faculty:</p>
            <ol style="font-size: 14px; text-align: left; max-width: 500px; margin: 0 auto;">
                <li>Use the registration system to register faculty members</li>
                <li>Make sure to fill in all required faculty information</li>
                <li>Faculty will appear here once registered</li>
            </ol>
            <p style="margin-top: 20px;">
                <a href="../New_registration/registration_page.php" class="register-link">Go to Registration Page</a>
            </p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>

</head>
<body>

<div id="facultyListSection" class="faculty-list">
    <h1>Faculty List</h1>

    <?php if (empty($db_error) && !empty($faculty)): ?>
        <!-- ✅ Search Bar (Top-Right) -->
        <div class="search-container">
            <input type="text" id="searchInput" class="search-input" placeholder="🔍 Tap to search...">
        </div>

        <div style="overflow-x: auto; margin-top: 10px;">
            <table id="facultyTable">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Faculty ID</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Designation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($faculty as $index => $faculty_member): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($faculty_member['faculty_id']); ?></td>
                            <td style="font-weight: bold; color: #003366;">
                                <?php echo htmlspecialchars($faculty_member['first_name'] . ' ' . $faculty_member['last_name']); ?>
                            </td>
                            <td><?php echo ucfirst(htmlspecialchars($faculty_member['gender'])); ?></td>
                            <td><?php echo htmlspecialchars($faculty_member['email']); ?></td>
                            <td><?php echo htmlspecialchars($faculty_member['department']); ?></td>
                            <td><?php echo htmlspecialchars($faculty_member['designation']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="text-align:center; margin-top:20px;">
                <a href="admin_front_page.php" 
                style="display:inline-block; background:#0066cc; color:white; padding:10px 20px; border-radius:6px; text-decoration:none;">
                ← Return to Dashboard
                </a>
            </div>
        </div>

        <script>
            // ✅ Live Search Filter
            document.getElementById('searchInput').addEventListener('keyup', function() {
                let filter = this.value.toLowerCase();
                let rows = document.querySelectorAll('#facultyTable tbody tr');

                rows.forEach(row => {
                    let text = row.innerText.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            });
        </script>

    <?php else: ?>
        <div class="no-data">
            <h3>⚠️ No Faculty Data Found</h3>
            <p>No faculty members have been registered yet.</p>
            <p style="font-size: 14px;">To add faculty:</p>
            <ol style="font-size: 14px; text-align: left; max-width: 500px; margin: 0 auto;">
                <li>Use the registration system to register faculty members</li>
                <li>Make sure to fill in all required faculty information</li>
                <li>Faculty will appear here once registered</li>
            </ol>
            <p style="margin-top: 20px;">
                <a href="../New_registration/registration_page.php" class="register-link">Go to Registration Page</a>
            </p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
