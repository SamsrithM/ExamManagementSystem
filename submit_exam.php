<?php
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

$test_id = $data['test_id'] ?? 0;
$student_name = $data['student_name'] ?? '';
$answers = $data['answers'] ?? [];

if(!$test_id || !$student_name || empty($answers)){
    echo json_encode(['status'=>'error','message'=>'Missing data']);
    exit;
}

$conn = new mysqli('localhost','root','','exam_system');
if($conn->connect_error){
    echo json_encode(['status'=>'error','message'=>$conn->connect_error]);
    exit;
}

// Insert result row
$stmt = $conn->prepare("INSERT INTO results (test_id, student_name) VALUES (?, ?)");
$stmt->bind_param("is", $test_id, $student_name);
$stmt->execute();
$result_id = $stmt->insert_id;
$stmt->close();

$correct_count = 0;

$stmt = $conn->prepare("INSERT INTO answers (result_id, question_id, answer, is_correct) VALUES (?, ?, ?, ?)");
foreach($answers as $a){
    $question_id = $a['question_id'];
    $answer = $a['answer'];
    
    // Check correctness for objective
    $res = $conn->query("SELECT correct_answer FROM questions WHERE id=$question_id AND type='objective'");
    $is_correct = null;
    if($res && $res->num_rows){
        $row = $res->fetch_assoc();
        $is_correct = ($row['correct_answer'] === $answer) ? 1 : 0;
        if($is_correct) $correct_count++;
    }

    $stmt->bind_param("iisi", $result_id, $question_id, $answer, $is_correct);
    $stmt->execute();
}
$stmt->close();

// Update total marks
$conn->query("UPDATE results SET total_marks=".count($answers).", obtained_marks=$correct_count WHERE id=$result_id");

$conn->close();
echo json_encode(['status'=>'success','total'=>count($answers),'score'=>$correct_count]);
?>
