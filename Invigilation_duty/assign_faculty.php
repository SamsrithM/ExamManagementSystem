<?php
header('Content-Type: application/json');

// Use environment variables for deployment
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db_name = getenv('ROOM_DB') ?: 'room_allocation';

// Read input JSON
$data = json_decode(file_get_contents("php://input"), true);
$classroom_id = $data['classroom_id'] ?? null;
$slot_date = $data['slot_date'] ?? null;
$exam_time = $data['slot_time'] ?? null;

if (!$classroom_id || !$slot_date || !$exam_time) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input: classroom, date or time missing']);
    exit;
}

// DB connection
$conn = new mysqli($host, $user, $pass, $db_name);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Get classroom name
$stmt = $conn->prepare("SELECT classroom_name FROM generated_classrooms WHERE id = ?");
$stmt->bind_param("i", $classroom_id);
$stmt->execute();
$stmt->bind_result($classroom_name);
$stmt->fetch();
$stmt->close();

// Check if already assigned
$stmt = $conn->prepare("SELECT faculty_name, email_id FROM faculty_assignments WHERE classroom_id = ?");
$stmt->bind_param("i", $classroom_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    $assigned = $res->fetch_assoc();
    echo json_encode([
        'status' => 'already_assigned',
        'message' => "This classroom is already assigned to {$assigned['faculty_name']} ({$assigned['email_id']})",
        'classroom' => $classroom_name,
        'faculty_name' => $assigned['faculty_name'],
        'email_id' => $assigned['email_id']
    ]);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Fetch all faculty slots for the date
$stmt = $conn->prepare("SELECT faculty_name, faculty_email, slot_time FROM faculty_slot_selection WHERE slot_date = ?");
$stmt->bind_param("s", $slot_date);
$stmt->execute();
$faculty_res = $stmt->get_result();

$eligible_faculty = [];
$exam_start = strtotime($exam_time);

while ($row = $faculty_res->fetch_assoc()) {
    list($start, $end) = explode(' - ', $row['slot_time']);
    $start_time = strtotime($start);
    $end_time = strtotime($end);

    if ($exam_start >= $start_time && $exam_start < $end_time) {
        // Check if faculty already assigned at same date & time
        $check_stmt = $conn->prepare("
            SELECT fa.id 
            FROM faculty_assignments fa
            INNER JOIN generated_classrooms gc ON fa.classroom_id = gc.id
            WHERE fa.email_id = ? AND gc.exam_date = ? AND gc.exam_time = ?
        ");
        $check_stmt->bind_param("sss", $row['faculty_email'], $slot_date, $exam_time);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result();
        if ($check_res->num_rows === 0) {
            $eligible_faculty[] = $row;
        }
        $check_stmt->close();
    }
}
$stmt->close();

if (count($eligible_faculty) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'No faculty available at this time']);
    $conn->close();
    exit;
}

// Random assignment
$assigned = $eligible_faculty[array_rand($eligible_faculty)];
$faculty_name = $assigned['faculty_name'];
$email_id = $assigned['faculty_email'];

// Insert assignment
$stmt = $conn->prepare("INSERT INTO faculty_assignments (classroom_id, faculty_name, email_id, classroom) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $classroom_id, $faculty_name, $email_id, $classroom_name);
$stmt->execute();

echo json_encode([
    'status' => 'success',
    'message' => "Faculty assigned successfully.",
    'classroom' => $classroom_name,
    'faculty_name' => $faculty_name,
    'email_id' => $email_id
]);

$stmt->close();
$conn->close();
?>
