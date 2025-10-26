<?php
// delete_classroom.php
header('Content-Type: application/json');
session_start();

// Detect environment
$env = getenv('RENDER') ? 'render' : 'local';

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid classroom ID!']);
    exit;
}

// Connect to DB
if ($env === 'local') {
    $conn = new mysqli('localhost', 'root', '', 'room_allocation');
    if ($conn->connect_error) {
        echo json_encode(['status' => 'error', 'message' => 'MySQL connection failed']);
        exit;
    }
    $conn->begin_transaction();
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

// Perform deletion
try {
    if ($env === 'local') {
        // Delete faculty assignments
        $stmt = $conn->prepare("DELETE FROM faculty_assignments WHERE classroom_id = ?");
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) throw new Exception("Failed to delete faculty assignments!");
        $stmt->close();

        // Delete classroom
        $stmt = $conn->prepare("DELETE FROM generated_classrooms WHERE id = ?");
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) throw new Exception("Failed to delete classroom!");
        $stmt->close();

        $conn->commit();
        $conn->close();
    } else {
        // PostgreSQL: start transaction
        pg_query($conn, "BEGIN");

        // Delete faculty assignments
        $res1 = pg_query_params($conn, "DELETE FROM faculty_assignments WHERE classroom_id=$1", [$id]);
        if ($res1 === false) throw new Exception("Failed to delete faculty assignments!");

        // Delete classroom
        $res2 = pg_query_params($conn, "DELETE FROM generated_classrooms WHERE id=$1", [$id]);
        if ($res2 === false) throw new Exception("Failed to delete classroom!");

        pg_query($conn, "COMMIT");
        pg_close($conn);
    }

    echo json_encode(['status' => 'success', 'message' => 'Classroom and related faculty assignments deleted successfully!']);
} catch (Exception $e) {
    if ($env === 'local') {
        $conn->rollback();
        $conn->close();
    } else {
        pg_query($conn, "ROLLBACK");
        pg_close($conn);
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
