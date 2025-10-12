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

$test_id = intval($_GET['test_id'] ?? 0);
if(!$test_id){
    echo json_encode(['status'=>'error','message'=>'Invalid test ID']);
    exit;
}

// Fetch questions for that test
$sql = "SELECT * FROM questions WHERE test_id = $test_id";
$result = $conn->query($sql);

$questions = [];
if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $questions[] = [
            'id' => $row['id'],
            'question_text' => $row['question_text'],
            'type' => $row['type'],
            'option_a' => $row['option_a'],
            'option_b' => $row['option_b'],
            'option_c' => $row['option_c'],
            'option_d' => $row['option_d'],
            'correct_answer' => $row['correct_answer'],
            'descriptive_answer' => $row['descriptive_answer']
        ];
    }
    echo json_encode(['status'=>'success','questions'=>$questions]);
} else {
    echo json_encode(['status'=>'error','message'=>'No questions found']);
}

$conn->close();
?>
