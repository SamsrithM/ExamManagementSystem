<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection
$servername = "localhost";
$username = "root";
$password = ""; // your DB password
$dbname = "exam_system";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['status'=>'error','message'=>'Database connection failed']);
    exit;
}

// Read JSON data
$data = json_decode(file_get_contents('php://input'), true);

$test_id = intval($data['test_id'] ?? 0);
$questions = $data['questions'] ?? [];

if(!$test_id || count($questions) == 0){
    echo json_encode(['status'=>'error','message'=>'Invalid data received']);
    exit;
}

foreach($questions as $q){
    $id = intval($q['id'] ?? 0);
    $question_text = $conn->real_escape_string($q['question_text'] ?? '');
    $type = $conn->real_escape_string($q['type'] ?? '');

    $option_a = $conn->real_escape_string($q['option_a'] ?? '');
    $option_b = $conn->real_escape_string($q['option_b'] ?? '');
    $option_c = $conn->real_escape_string($q['option_c'] ?? '');
    $option_d = $conn->real_escape_string($q['option_d'] ?? '');
    $correct_answer = $conn->real_escape_string($q['correct_answer'] ?? '');
    $desc_answer = $conn->real_escape_string($q['descriptive_answer'] ?? '');

    // Update query
    $sql = "UPDATE questions SET 
            question_text='$question_text', 
            type='$type', 
            option_a='$option_a', 
            option_b='$option_b', 
            option_c='$option_c', 
            option_d='$option_d', 
            correct_answer='$correct_answer', 
            descriptive_answer='$desc_answer'
            WHERE id=$id AND test_id=$test_id";

    $conn->query($sql);
}

echo json_encode(['status'=>'success','message'=>'Questions updated successfully']);
$conn->close();
?>
