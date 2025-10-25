<?php
// delete_classroom.php
header('Content-Type: application/json');

// Use environment variables for deployment
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db_name = getenv('ROOM_DB') ?: 'room_allocation';

// Connect to DB
$conn = new mysqli($host, $user, $pass, $db_name);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid classroom ID!']);
    exit;
}

$conn->begin_transaction();

try {
    // Delete faculty assignments
    $stmt = $conn->prepare("DELETE FROM faculty_assignments WHERE classroom_id = ?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) throw new Exception("Failed to delete faculty assignments!");
    $stmt->close();

    // Delete the classroom
    $stmt = $conn->prepare("DELETE FROM generated_classrooms WHERE id = ?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) throw new Exception("Failed to delete classroom!");
    $stmt->close();

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Classroom and related faculty assignments deleted successfully!']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>
