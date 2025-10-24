<?php
header('Content-Type: application/json');

// Enable errors for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "room_allocation";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['classrooms']) || !is_array($data['classrooms']) || count($data['classrooms']) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'No classrooms to save!']);
    exit;
}

$classrooms = $data['classrooms'];
$successCount = 0;
$errors = [];

foreach ($classrooms as $classroom) {
    // Make sure all required fields exist
    if (!isset($classroom['name'], $classroom['date'], $classroom['time'])) {
        $errors[] = "Classroom data incomplete!";
        continue;
    }

    $classroom_name = trim($conn->real_escape_string($classroom['name']));
    $exam_date = trim($conn->real_escape_string($classroom['date']));
    $exam_time = trim($conn->real_escape_string($classroom['time']));
    $created_at = date('Y-m-d H:i:s');

    if ($classroom_name === '' || $exam_date === '' || $exam_time === '') {
        $errors[] = "Classroom name, date, or time cannot be empty!";
        continue;
    }

    $sql = "INSERT INTO generated_classrooms (classroom_name, exam_date, exam_time, created_at) 
            VALUES ('$classroom_name', '$exam_date', '$exam_time', '$created_at')";

    if ($conn->query($sql)) {
        $successCount++;
    } else {
        $errors[] = "Failed to insert '{$classroom_name}': " . $conn->error;
    }
}

$conn->close();

if ($successCount > 0) {
    $message = "$successCount classrooms saved successfully!";
    if (!empty($errors)) $message .= " Some errors occurred: " . implode('; ', $errors);
    echo json_encode(['status' => 'success', 'message' => $message]);
} else {
    $message = !empty($errors) ? implode('; ', $errors) : 'No classrooms were saved!';
    echo json_encode(['status' => 'error', 'message' => $message]);
}
?>
