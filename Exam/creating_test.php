<?php
// --- PHP: Handle saving questions from AJAX request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $test_id = $data['test_id'] ?? null;
    $questions = $data['questions'] ?? [];

    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "test_creation";

    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
    }

    if (empty($test_id) || empty($questions)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing test_id or questions']);
        exit;
    }

    $errors = [];
    foreach ($questions as $q) {
        $question   = $conn->real_escape_string($q['question'] ?? '');
        $type       = 'objective'; // Only objective now
        $options    = isset($q['options']) ? json_encode($q['options']) : null;
        $answer     = $q['answer'] ?? null;

        if (!$question || !$type || !$options || !$answer) {
            $errors[] = "Question, options or correct answer missing.";
            continue;
        }

        $sql = "INSERT INTO questions (test_id, question_text, question_type, options, correct_answer)
                VALUES ('$test_id', '$question', '$type', '$options', '$answer')";

        if (!$conn->query($sql)) {
            $errors[] = "Insert failed for question: {$question}. Error: " . $conn->error;
        }
    }

    if (!empty($errors)) {
        echo json_encode(['status' => 'error', 'message' => implode("\n", $errors)]);
    } else {
        echo json_encode(['status' => 'success', 'message' => 'Questions saved']);
    }

    $conn->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Create Questions - Faculty Dashboard</title>
<style>
body { margin:0; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background-color:#f4f7f9; }
form { background:white; padding:25px 30px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1); max-width:800px; margin:30px auto; display:flex; flex-direction:column; gap:15px; }
.question-block { border:1px solid #ccc; padding:20px; border-radius:8px; background:#fafafa; position:relative; }
label { display:block; font-weight:600; margin-bottom:5px; }
input, select { width:100%; padding:10px; margin-bottom:10px; border:1px solid #ccc; border-radius:6px; }
button { padding:12px 20px; font-size:1rem; background-color:#1abc9c; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:700; margin-top:10px; }
button:hover { background-color:#159a85; }
.delete-question-btn { background:#e74c3c; color:white; border:none; border-radius:4px; padding:5px 10px; cursor:pointer; }
.delete-question-btn:hover { background:#c0392b; }
#addQuestionBtn { background:#3498db; margin-top:10px; align-self:flex-start; }
#addQuestionBtn:hover { background:#2980b9; }

.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); align-items:center; justify-content:center; z-index:1000; animation:fadeIn 0.3s ease-in-out; }
.modal-content { background:white; padding:25px 40px; border-radius:12px; text-align:center; box-shadow:0 5px 20px rgba(0,0,0,0.3); animation:scaleUp 0.3s ease-in-out; }
.modal-content h3 { color:#2ecc71; margin:0; }
.modal-content button { margin-top:15px; background:#2ecc71; color:white; border:none; padding:8px 18px; border-radius:6px; cursor:pointer; }
.modal-content button:hover { background:#27ae60; }

@keyframes fadeIn { from {opacity:0;} to {opacity:1;} }
@keyframes scaleUp { from {transform:scale(0.8);} to {transform:scale(1);} }
</style>
</head>
<body>

<div id="successModal" class="modal">
  <div class="modal-content">
    <h3>✅ Questions Saved Successfully!</h3>
    <button id="okBtn">OK</button>
  </div>
</div>

<form id="questionsForm">
  <button type="button" id="addQuestionBtn">+ Add Question</button>
  <button type="submit">Publish Questions</button>
</form>

<script>
let questionCount = 0;
const addBtn = document.getElementById('addQuestionBtn');
const form = document.getElementById('questionsForm');

function addQuestion() {
  questionCount++;
  const block = document.createElement('div');
  block.className = 'question-block';
  block.dataset.id = questionCount;

  block.innerHTML = `
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
      <label class="question-label" for="question${questionCount}">Question ${questionCount}</label>
      <button type="button" class="delete-question-btn" onclick="deleteQuestion(${questionCount})">Delete</button>
    </div>

    <input type="text" id="question${questionCount}" name="question${questionCount}" placeholder="Enter question here..." required />

    <div id="options${questionCount}" style="margin-top:10px;">
      <label>Option A</label><input type="text" name="option${questionCount}_1" placeholder="Option A" required />
      <label>Option B</label><input type="text" name="option${questionCount}_2" placeholder="Option B" required />
      <label>Option C</label><input type="text" name="option${questionCount}_3" placeholder="Option C" required />
      <label>Option D</label><input type="text" name="option${questionCount}_4" placeholder="Option D" required />
      <label>Correct Answer</label>
      <select name="answer${questionCount}" required>
        <option value="" disabled selected>Select correct</option>
        <option value="A">A</option>
        <option value="B">B</option>
        <option value="C">C</option>
        <option value="D">D</option>
      </select>
    </div>
  `;

  form.insertBefore(block, addBtn);
}

function deleteQuestion(id) {
  const block = document.querySelector(`.question-block[data-id="${id}"]`);
  if (block) block.remove();
  renumberQuestions();
}

function renumberQuestions() {
  const blocks = document.querySelectorAll('.question-block');
  questionCount = blocks.length;
  blocks.forEach((block, index) => {
    const newNum = index + 1;
    const oldNum = block.dataset.id;
    block.dataset.id = newNum;
    block.querySelector('.question-label').textContent = `Question ${newNum}`;
    block.querySelector('input').id = `question${newNum}`;
    const optionsDiv = block.querySelector(`#options${oldNum}`);
    if(optionsDiv) optionsDiv.id = `options${newNum}`;
  });
}

addBtn.addEventListener('click', addQuestion);

document.getElementById('questionsForm').addEventListener('submit', function(e){
  e.preventDefault();

  const test_id = localStorage.getItem('current_test_id');
  if(!test_id){ alert('Test ID not found.'); return; }

  const questions = [];
  let hasError = false;

  document.querySelectorAll('.question-block').forEach((block, index) => {
    const qid = block.dataset.id;
    const questionText = block.querySelector(`#question${qid}`).value.trim();
    const opts = [...block.querySelectorAll(`#options${qid} input`)].map(i => i.value.trim());
    const ans = block.querySelector(`#options${qid} select`).value;

    if(!questionText || opts.some(o => o === '') || !ans){
      alert(`Please fill all fields for Question ${index+1}`);
      hasError = true;
      return;
    }

    questions.push({ question: questionText, type: 'objective', options: opts, answer: ans });
  });

  if(hasError) return;

  fetch('creating_test.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({test_id, questions})
  })
  .then(res => res.json())
  .then(data => {
    if(data.status === 'success'){
      document.getElementById("successModal").style.display = "flex";
    } else {
      alert('Error: ' + data.message);
    }
  })
  .catch(err => { 
    console.error(err);
    alert('Error: ' + err);
  });
});

document.getElementById("okBtn").addEventListener("click", ()=>{
  document.getElementById("successModal").style.display="none";
  window.location.href="reviewquestions.php";
});

window.onload = addQuestion;
</script>
</body>
</html>
