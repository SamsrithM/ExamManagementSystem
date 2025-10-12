<?php
header('Content-Type: application/json');

// Get the JSON data from the frontend
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
$branch = $data['branch'] ?? '';
$title = $data['title'] ?? '';
$date = $data['date'] ?? '';
$available_from = $data['availableFrom'] ?? '';
$duration = $data['duration'] ?? 0;
$type = $data['type'] ?? '';

if (!$branch || !$title || !$date || !$available_from || !$duration || !$type) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}

// Connect to MySQL
$conn = new mysqli('localhost', 'root', '', 'exam_system');

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// Prepare and execute the insert query
$stmt = $conn->prepare("INSERT INTO tests (branch, title, test_date, available_from, duration, type) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssds", $branch, $title, $date, $available_from, $duration, $type);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'test_id' => $stmt->insert_id]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to create test']);
}

$stmt->close();
$conn->close();
?>
