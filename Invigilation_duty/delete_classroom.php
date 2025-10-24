<?php
// delete_classroom.php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "room_allocation";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Connection failed']));
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);

if ($id > 0) {
    // Begin transaction
    $conn->begin_transaction();
    try {
        // Delete faculty assignments related to this classroom
        $sql_assignments = "DELETE FROM faculty_assignments WHERE classroom_id = $id";
        if (!$conn->query($sql_assignments)) {
            throw new Exception("Failed to delete faculty assignments!");
        }

        // Delete the classroom
        $sql_classroom = "DELETE FROM generated_classrooms WHERE id = $id";
        if (!$conn->query($sql_classroom)) {
            throw new Exception("Failed to delete classroom!");
        }

        // Commit transaction
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Classroom and related faculty assignments deleted successfully!']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid classroom ID!']);
}

$conn->close();
?>
