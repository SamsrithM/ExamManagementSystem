<?php
header('Content-Type: application/json');

// Detect environment
$env = getenv('RENDER') ? 'render' : 'local';

// DB connection
if ($env === 'local') {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $db_name = getenv('DB_ROOM') ?: 'room_allocation';

    $conn = new mysqli($host, $user, $pass, $db_name);
    $conn->set_charset("utf8");
} else {
    $db_host = getenv('DB_HOST');
    $db_port = getenv('DB_PORT') ?: '5432';
    $db_user = getenv('DB_USER');
    $db_pass = getenv('DB_PASS');
    $db_name = getenv('DB_ROOM') ?: 'room_allocation';

    $conn = pg_connect("host=$db_host port=$db_port dbname=$db_name user=$db_user password=$db_pass");
    if (!$conn) {
        echo json_encode(['status' => 'error', 'message' => 'PostgreSQL connection failed!']);
        exit;
    }
}

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

    if ($env === 'local') {
        $stmt = $conn->prepare("INSERT INTO generated_classrooms (classroom_name, exam_date, exam_time, created_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $classroom_name, $exam_date, $exam_time, $created_at);
        if ($stmt->execute()) $successCount++;
        else $errors[] = "Failed to insert '{$classroom_name}': " . $stmt->error;
        $stmt->close();
    } else {
        $res = pg_query_params($conn,
            "INSERT INTO generated_classrooms (classroom_name, exam_date, exam_time, created_at) VALUES ($1, $2, $3, $4)",
            [$classroom_name, $exam_date, $exam_time, $created_at]
        );
        if ($res) $successCount++;
        else $errors[] = "Failed to insert '{$classroom_name}'";
    }
}

// Close connection
if ($env === 'local') $conn->close();
else pg_close($conn);

// Prepare response
if ($successCount > 0) {
    $message = "$successCount classrooms saved successfully!";
    if (!empty($errors)) $message .= " Some errors occurred: " . implode('; ', $errors);
    echo json_encode(['status' => 'success', 'message' => $message]);
} else {
    $message = !empty($errors) ? implode('; ', $errors) : 'No classrooms were saved!';
    echo json_encode(['status' => 'error', 'message' => $message]);
}
?>
