<?php
// assign_faculty.php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "room_allocation";

// Get input
$data = json_decode(file_get_contents("php://input"), true);
$classroom_id = $data['classroom_id'] ?? null;
$slot_date = $data['slot_date'] ?? null;
$exam_time = $data['slot_time'] ?? null; // classroom start time

if (!$classroom_id || !$slot_date || !$exam_time) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input: classroom, date or time missing']);
    exit;
}

// DB connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'DB connection failed']);
    exit;
}

// Get classroom name
$classroom_res = $conn->query("SELECT classroom_name FROM generated_classrooms WHERE id='$classroom_id'");
$classroom_name = $classroom_res->fetch_assoc()['classroom_name'] ?? '';

// Check if classroom already assigned
$check_res = $conn->query("SELECT faculty_name, email_id FROM faculty_assignments WHERE classroom_id='$classroom_id'");
if ($check_res->num_rows > 0) {
    $assigned = $check_res->fetch_assoc();
    echo json_encode([
        'status' => 'already_assigned',
        'message' => "This classroom is already assigned to {$assigned['faculty_name']} ({$assigned['email_id']})",
        'classroom' => $classroom_name,
        'faculty_name' => $assigned['faculty_name'],
        'email_id' => $assigned['email_id']
    ]);
    $conn->close();
    exit;
}

// Fetch all faculty who selected slots on this date
$faculty_res = $conn->query("SELECT * FROM faculty_slot_selection WHERE slot_date='$slot_date'");

$eligible_faculty = [];
while ($row = $faculty_res->fetch_assoc()) {
    // Convert faculty slot time "01:20 - 02:20" into start and end
    list($start, $end) = explode(' - ', $row['slot_time']);
    $start_time = date("H:i:s", strtotime($start));
    $end_time = date("H:i:s", strtotime($end));
    $exam_start = date("H:i:s", strtotime($exam_time));

    // Check if exam start time falls in the faculty slot
    if ($exam_start >= $start_time && $exam_start < $end_time) {

        // Check if this faculty is already assigned for this date & time
        $faculty_email = $row['faculty_email'];
        $already_assigned_res = $conn->query("
            SELECT * FROM faculty_assignments fa
            INNER JOIN generated_classrooms gc 
            ON fa.classroom_id = gc.id
            WHERE fa.email_id = '$faculty_email' 
              AND gc.exam_date = '$slot_date' 
              AND gc.exam_time = '$exam_time'
        ");

        if ($already_assigned_res->num_rows == 0) {
            $eligible_faculty[] = $row;
        }
    }
}

// If no eligible faculty
if (count($eligible_faculty) == 0) {
    echo json_encode(['status' => 'error', 'message' => 'No faculty available at this time']);
    $conn->close();
    exit;
}

// Randomly pick one eligible faculty
$assigned = $eligible_faculty[array_rand($eligible_faculty)];
$faculty_name = $assigned['faculty_email']; // or use $assigned['faculty_name'] if available
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
