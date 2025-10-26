<?php
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Database connection using environment variables
$servername = getenv('DB_HOST') ?: '127.0.0.1';
$username   = getenv('DB_USER') ?: 'root';
$password   = getenv('DB_PASS') ?: '';
$dbname     = getenv('ROOM_DB') ?: 'room_allocation';

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

$stmt = $conn->prepare("INSERT INTO generated_classrooms (classroom_name, exam_date, exam_time, created_at) VALUES (?, ?, ?, ?)");

foreach ($classrooms as $classroom) {
    // Validate fields
    if (!isset($classroom['name'], $classroom['date'], $classroom['time'])) {
        $errors[] = "Classroom data incomplete!";
        continue;
    }

    $classroom_name = trim($classroom['name']);
    $exam_date      = trim($classroom['date']);
    $exam_time      = trim($classroom['time']);
    $created_at     = date('Y-m-d H:i:s');

    if ($classroom_name === '' || $exam_date === '' || $exam_time === '') {
        $errors[] = "Classroom name, date, or time cannot be empty!";
        continue;
    }

    $stmt->bind_param("ssss", $classroom_name, $exam_date, $exam_time, $created_at);

    if ($stmt->execute()) {
        $successCount++;
    } else {
        $errors[] = "Failed to insert '{$classroom_name}': " . $stmt->error;
    }
}

$stmt->close();
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
