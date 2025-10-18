<!DOCTYPE html>
<html>
<head>
    <title>Check Students Database</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>Checking Students in new_registration_data Database</h2>
    
    <?php
    $db_host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "new_registration_data";

    echo "<p>Checking students in new_registration_data database...</p>";

    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        echo "<p class='error'>❌ Database connection failed: " . $conn->connect_error . "</p>";
        echo "<p>This means the new_registration_data database doesn't exist yet.</p>";
        echo "<p>To fix this:</p>";
        echo "<ol>";
        echo "<li>Go to <a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a></li>";
        echo "<li>Import the file: New_registration/registration_database.sql</li>";
        echo "<li>Or use the registration system to create the database</li>";
        echo "</ol>";
        exit;
    }

    echo "<p class='success'>✅ Connected to new_registration_data database</p>";

    // Check if students_new_data table exists
    $result = $conn->query("SHOW TABLES LIKE 'students_new_data'");
    if ($result->num_rows == 0) {
        echo "<p class='error'>❌ students_new_data table does not exist!</p>";
        exit;
    }

    echo "<p class='success'>✅ students_new_data table exists</p>";

    // Count total students
    $result = $conn->query("SELECT COUNT(*) as count FROM students_new_data");
    $count = $result->fetch_assoc();
    echo "<p><strong>📊 Total students: " . $count['count'] . "</strong></p>";

    if ($count['count'] > 0) {
        // Show all students
        echo "<h3>All Students in Database:</h3>";
        $result = $conn->query("SELECT first_name, last_name, department, batch, roll_number, institute_email FROM students_new_data ORDER BY department, batch, roll_number");
        
        echo "<table>";
        echo "<tr><th>Name</th><th>Department</th><th>Batch</th><th>Roll Number</th><th>Email</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['first_name'] . " " . $row['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['department']) . "</td>";
            echo "<td>" . htmlspecialchars($row['batch']) . "</td>";
            echo "<td>" . htmlspecialchars($row['roll_number']) . "</td>";
            echo "<td>" . htmlspecialchars($row['institute_email']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check what programs exist in database
        echo "<h3>Programs in Database:</h3>";
        $result = $conn->query("SELECT DISTINCT course FROM students_new_data ORDER BY course");
        echo "<ul>";
        while ($row = $result->fetch_assoc()) {
            echo "<li><strong>" . htmlspecialchars($row['course']) . "</strong></li>";
        }
        echo "</ul>";
        
        // Check specific departments and batches
        echo "<h3>Students by Program, Department and Batch:</h3>";
        $programs = [];
        $result = $conn->query("SELECT DISTINCT course FROM students_new_data");
        while ($row = $result->fetch_assoc()) {
            $programs[] = $row['course'];
        }
        
        $departments = ['CSE', 'ECE', 'MECH'];
        $batches = [2022, 2023, 2024, 2025];
        
        echo "<table>";
        echo "<tr><th>Program</th><th>Department</th><th>Batch</th><th>Count</th><th>Action</th></tr>";
        foreach ($programs as $program) {
            foreach ($departments as $dept) {
                foreach ($batches as $batch) {
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM students_new_data WHERE course = ? AND department = ? AND batch = ?");
                    $stmt->bind_param("ssi", $program, $dept, $batch);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $count = $result->fetch_assoc();
                    if ($count['count'] > 0) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($program) . "</td>";
                        echo "<td>" . $dept . "</td>";
                        echo "<td>" . $batch . "</td>";
                        echo "<td>" . $count['count'] . "</td>";
                        echo "<td><a href='Admin/admin_front_page.php?program=" . urlencode($program) . "&dept=" . $dept . "&batch=" . $batch . "'>View in Admin Dashboard</a></td>";
                        echo "</tr>";
                    }
                    $stmt->close();
                }
            }
        }
        echo "</table>";
        
    } else {
        echo "<p class='warning'>⚠️ No students found in database!</p>";
        echo "<p>You need to register students first using the registration system.</p>";
        echo "<p><a href='New_registration/registration_page.php'>Go to Registration Page</a></p>";
    }

    $conn->close();
    ?>
    
    <p><a href="Admin/admin_front_page.php">← Back to Admin Dashboard</a></p>
</body>
</html>


