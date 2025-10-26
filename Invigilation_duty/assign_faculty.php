<?php
header('Content-Type: application/json');

session_start();

// Detect environment
$env = getenv('RENDER') ? 'render' : 'local';

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
if ($env === 'local') {
    $conn = new mysqli('localhost', 'root', '', 'room_allocation');
    if ($conn->connect_error) {
        echo json_encode(['status' => 'error', 'message' => 'MySQL connection failed']);
        exit;
    }
} else {
    $db_host = getenv('DB_HOST');
    $db_port = getenv('DB_PORT') ?: '5432';
    $db_user = getenv('DB_USER');
    $db_pass = getenv('DB_PASS');
    $db_name = getenv('DB_ROOM') ?: 'room_allocation';
    $conn = pg_connect("host=$db_host port=$db_port dbname=$db_name user=$db_user password=$db_pass");
    if (!$conn) {
        echo json_encode(['status' => 'error', 'message' => 'PostgreSQL connection failed']);
        exit;
    }
}

// Get classroom name
$classroom_name = '';
if ($env === 'local') {
    $stmt = $conn->prepare("SELECT classroom_name FROM generated_classrooms WHERE id = ?");
    $stmt->bind_param("i", $classroom_id);
    $stmt->execute();
    $stmt->bind_result($classroom_name);
    $stmt->fetch();
    $stmt->close();
} else {
    $res = pg_query_params($conn, "SELECT classroom_name FROM generated_classrooms WHERE id=$1", [$classroom_id]);
    $classroom_name = pg_fetch_result($res, 0, 0);
}

// Check if already assigned
if ($env === 'local') {
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
} else {
    $res = pg_query_params($conn, "SELECT faculty_name, email_id FROM faculty_assignments WHERE classroom_id=$1", [$classroom_id]);
    if (pg_num_rows($res) > 0) {
        $assigned = pg_fetch_assoc($res);
        echo json_encode([
            'status' => 'already_assigned',
            'message' => "This classroom is already assigned to {$assigned['faculty_name']} ({$assigned['email_id']})",
            'classroom' => $classroom_name,
            'faculty_name' => $assigned['faculty_name'],
            'email_id' => $assigned['email_id']
        ]);
        pg_close($conn);
        exit;
    }
}

// Fetch all faculty slots for the date
$faculty_slots = [];
if ($env === 'local') {
    $stmt = $conn->prepare("SELECT faculty_name, faculty_email, slot_time FROM faculty_slot_selection WHERE slot_date = ?");
    $stmt->bind_param("s", $slot_date);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $faculty_slots[] = $row;
    $stmt->close();
} else {
    $res = pg_query_params($conn, "SELECT faculty_name, faculty_email, slot_time FROM faculty_slot_selection WHERE slot_date=$1", [$slot_date]);
    while ($row = pg_fetch_assoc($res)) $faculty_slots[] = $row;
}

// Find eligible faculty
$eligible_faculty = [];
$exam_start = strtotime($exam_time);

foreach ($faculty_slots as $row) {
    list($start, $end) = explode(' - ', $row['slot_time']);
    $start_time = strtotime($start);
    $end_time = strtotime($end);

    if ($exam_start >= $start_time && $exam_start < $end_time) {
        // Check if faculty already assigned
        if ($env === 'local') {
            $stmt = $conn->prepare("SELECT fa.id 
                FROM faculty_assignments fa
                INNER JOIN generated_classrooms gc ON fa.classroom_id = gc.id
                WHERE fa.email_id=? AND gc.exam_date=? AND gc.exam_time=?");
            $stmt->bind_param("sss", $row['faculty_email'], $slot_date, $exam_time);
            $stmt->execute();
            $check_res = $stmt->get_result();
            if ($check_res->num_rows === 0) $eligible_faculty[] = $row;
            $stmt->close();
        } else {
            $check_res = pg_query_params($conn, "SELECT fa.id 
                FROM faculty_assignments fa
                INNER JOIN generated_classrooms gc ON fa.classroom_id = gc.id
                WHERE fa.email_id=$1 AND gc.exam_date=$2 AND gc.exam_time=$3", [$row['faculty_email'], $slot_date, $exam_time]);
            if (pg_num_rows($check_res) === 0) $eligible_faculty[] = $row;
        }
    }
}

if (empty($eligible_faculty)) {
    echo json_encode(['status' => 'error', 'message' => 'No faculty available at this time']);
    if ($env === 'local') $conn->close(); else pg_close($conn);
    exit;
}

// Random assignment
$assigned = $eligible_faculty[array_rand($eligible_faculty)];
$faculty_name = $assigned['faculty_name'];
$email_id = $assigned['faculty_email'];

// Insert assignment
if ($env === 'local') {
    $stmt = $conn->prepare("INSERT INTO faculty_assignments (classroom_id, faculty_name, email_id, classroom) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $classroom_id, $faculty_name, $email_id, $classroom_name);
    $stmt->execute();
    $stmt->close();
    $conn->close();
} else {
    pg_query_params($conn, "INSERT INTO faculty_assignments (classroom_id, faculty_name, email_id, classroom) VALUES ($1,$2,$3,$4)",
        [$classroom_id, $faculty_name, $email_id, $classroom_name]);
    pg_close($conn);
}

echo json_encode([
    'status' => 'success',
    'message' => "Faculty assigned successfully.",
    'classroom' => $classroom_name,
    'faculty_name' => $faculty_name,
    'email_id' => $email_id
]);
?>
