<?php
header('Content-Type: application/json');

// Get raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
$test_id = $data['test_id'] ?? null;
$questions = $data['questions'] ?? [];

if(!$test_id || empty($questions)){
    echo json_encode(['status'=>'error','message'=>'Test ID or questions missing']);
    exit;
}

// Connect to database
$conn = new mysqli('localhost','root','','exam_system');
if($conn->connect_error){
    echo json_encode(['status'=>'error','message'=>$conn->connect_error]);
    exit;
}

// Prepare insert statement
$stmt = $conn->prepare("INSERT INTO questions (test_id, question_text, type, option_a, option_b, option_c, option_d, correct_answer, descriptive_answer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

if(!$stmt){
    echo json_encode(['status'=>'error','message'=>'Prepare failed: '.$conn->error]);
    exit;
}

// Insert each question
foreach($questions as $q){
    $question_text = $q['question'] ?? '';
    $type = $q['type'] ?? '';
    
    $optA = ($type === 'objective') ? ($q['options'][0] ?? '') : null;
    $optB = ($type === 'objective') ? ($q['options'][1] ?? '') : null;
    $optC = ($type === 'objective') ? ($q['options'][2] ?? '') : null;
    $optD = ($type === 'objective') ? ($q['options'][3] ?? '') : null;
    $answer = ($type === 'objective') ? ($q['answer'] ?? '') : null;
    $desc = ($type === 'descriptive') ? ($q['descriptiveAnswer'] ?? '') : null;

    $stmt->bind_param("issssssss", $test_id, $question_text, $type, $optA, $optB, $optC, $optD, $answer, $desc);
    
    if(!$stmt->execute()){
        echo json_encode(['status'=>'error','message'=>'Failed to insert question: '.$stmt->error]);
        $stmt->close();
        $conn->close();
        exit;
    }
}

$stmt->close();
$conn->close();

// Success
echo json_encode(['status'=>'success']);
?>
