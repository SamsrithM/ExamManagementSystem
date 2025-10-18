<?php
// Check students in new_registration_data database
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "new_registration_data";

echo "Checking students in new_registration_data database...\n\n";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    echo "❌ Database connection failed: " . $conn->connect_error . "\n";
    exit;
}

echo "✅ Connected to new_registration_data database\n\n";

// Check if students_new_data table exists
$result = $conn->query("SHOW TABLES LIKE 'students_new_data'");
if ($result->num_rows == 0) {
    echo "❌ students_new_data table does not exist!\n";
    exit;
}

echo "✅ students_new_data table exists\n\n";

// Count total students
$result = $conn->query("SELECT COUNT(*) as count FROM students_new_data");
$count = $result->fetch_assoc();
echo "📊 Total students: " . $count['count'] . "\n\n";

if ($count['count'] > 0) {
    // Show all students with their department and batch
    echo "All students in database:\n";
    echo "----------------------------------------\n";
    $result = $conn->query("SELECT first_name, last_name, department, batch, roll_number FROM students_new_data ORDER BY department, batch, roll_number");
    while ($row = $result->fetch_assoc()) {
        echo "Name: " . $row['first_name'] . " " . $row['last_name'] . "\n";
        echo "Department: " . $row['department'] . "\n";
        echo "Batch: " . $row['batch'] . "\n";
        echo "Roll: " . $row['roll_number'] . "\n";
        echo "----------------------------------------\n";
    }
    
    // Check specific departments and batches
    echo "\nChecking specific departments and batches:\n";
    $departments = ['CSE', 'ECE', 'MECH'];
    $batches = [2022, 2023, 2024, 2025];
    
    foreach ($departments as $dept) {
        foreach ($batches as $batch) {
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM students_new_data WHERE department = ? AND batch = ?");
            $stmt->bind_param("si", $dept, $batch);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc();
            echo "$dept $batch: " . $count['count'] . " students\n";
            $stmt->close();
        }
    }
} else {
    echo "⚠️ No students found in database!\n";
    echo "You need to register students first using the registration system.\n";
}

$conn->close();
?>


