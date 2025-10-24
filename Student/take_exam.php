<?php
session_start();
if (!isset($_SESSION['roll_number'])) {
    header("Location: student_login.php");
    exit;
}

$roll_number = $_SESSION['roll_number'];
$test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;

if ($test_id <= 0) {
    die("Invalid exam selected.");
}

// DB connection
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "test_creation";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

// Get test title and duration
$stmt = $conn->prepare("SELECT test_title, duration, branch, test_date FROM tests WHERE test_id=?");
$stmt->bind_param("i", $test_id);
$stmt->execute();
$result = $stmt->get_result();
$test = $result->fetch_assoc();
$stmt->close();

if (!$test) {
    die("Exam not found.");
}
$duration_minutes = intval($test['duration']);

// Fetch only multiple choice questions
$stmt = $conn->prepare("SELECT id, question_text, options, correct_answer 
                        FROM questions 
                        WHERE test_id=? AND question_type='objective' 
                        ORDER BY id ASC");
$stmt->bind_param("i", $test_id);
$stmt->execute();
$result = $stmt->get_result();
$questions = [];
while ($row = $result->fetch_assoc()) {
    $decoded_options = json_decode($row['options'], true);
    if (!is_array($decoded_options)) {
        $decoded_options = [$row['options']];
    }
    $row['options'] = $decoded_options;
    $questions[] = $row;
}
$stmt->close();
$conn->close();

if (empty($questions)) {
    die("No multiple-choice questions found for this exam.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Take Exam - <?= htmlspecialchars($test['test_title']) ?></title>
<style>
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin:0; background:#f4f7f9; color:#2c3e50; }
.navbar { background:#2c3e50; padding:0 20px; display:flex; align-items:center; box-shadow:0 2px 4px rgba(0,0,0,0.1); }
.navbar a { color:white; text-decoration:none; padding:15px 20px; font-weight:500; transition:background 0.3s; }
.navbar a:hover { background:#1abc9c; color:black; }
.container { max-width:900px; margin:20px auto; padding:20px; }
.header { background:white; padding:20px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1); margin-bottom:20px; }
.card { background:white; padding:20px; margin-bottom:15px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
.question-text { font-size:18px; margin-bottom:10px; font-weight:600; }
.options label { display:block; margin-bottom:8px; cursor:pointer; }
button { padding:10px 20px; border:none; border-radius:5px; cursor:pointer; font-weight:bold; }
.save-btn { background:#1abc9c; color:white; margin-top:10px; }
.save-btn:hover { background:#16a085; }
.submit-btn { background:#e74c3c; color:white; margin-top:20px; width:100%; font-size:18px; }
.submit-btn:hover { background:#c0392b; }
.progress-bar { height:10px; background:#ddd; border-radius:5px; overflow:hidden; margin-bottom:20px; }
.progress { height:10px; background:#1abc9c; width:0%; transition:0.3s; }
#timer { font-size:18px; color:#e74c3c; text-align:right; margin-bottom:10px; }
@media(max-width:600px){ .question-text{font-size:16px;} button{font-size:14px;padding:8px 16px;} }
</style>
</head>
<body>

<div class="navbar">
    <a href="#">📋 Exam: <?= htmlspecialchars($test['test_title']) ?></a>
</div>

<div class="container">
    <div class="header">
        <h1>Exam: <?= htmlspecialchars($test['test_title']) ?></h1>
        <p><strong>Branch:</strong> <?= htmlspecialchars($test['branch']) ?> |
           <strong>Date:</strong> <?= $test['test_date'] ?> |
           <strong>Duration:</strong> <?= $test['duration'] ?> minutes</p>
    </div>

    <div id="timer">Time Left: <span id="time"><?= $duration_minutes ?>:00</span></div>
    <div class="progress-bar"><div class="progress" id="progress"></div></div>
    <div id="question-container"></div>
    <button id="prev-btn" style="display:none;">⬅ Previous</button>
    <button id="next-btn">Next ➡</button>
    <button id="submit-btn" class="submit-btn" style="display:none;">Submit Exam</button>
</div>

<script>
const questions = <?= json_encode($questions); ?>;
const testId = <?= $test_id; ?>;
const rollNumber = "<?= $roll_number ?>";
let currentIndex = 0;
let answers = JSON.parse(localStorage.getItem('exam_answers_' + rollNumber + '_' + testId)) || {};

const questionContainer = document.getElementById('question-container');
const prevBtn = document.getElementById('prev-btn');
const nextBtn = document.getElementById('next-btn');
const submitBtn = document.getElementById('submit-btn');
const progressEl = document.getElementById('progress');

// Modal for submission
const modal = document.createElement('div');
modal.style.display = 'none';
modal.style.position = 'fixed';
modal.style.top = 0;
modal.style.left = 0;
modal.style.width = '100%';
modal.style.height = '100%';
modal.style.background = 'rgba(0,0,0,0.5)';
modal.style.color = 'white';
modal.style.fontSize = '24px';
modal.style.textAlign = 'center';
modal.style.paddingTop = '200px';
modal.style.zIndex = 10000;
modal.innerText = 'Submitting your exam...';
document.body.appendChild(modal);

function renderQuestion() {
    const q = questions[currentIndex];
    let html = `<div class="card"><div class="question-text">Q${currentIndex+1}. ${q.question_text}</div>`;
    
    html += '<div class="options">';
    
    q.options.forEach((opt, idx) => {
        const letter = String.fromCharCode(65 + idx); // "A", "B", "C", "D"
        const checked = (answers[q.id] === letter) ? 'checked' : '';
        html += `<label>
                    <input type="radio" name="option" value="${letter}" data-letter="${letter}" ${checked}> ${letter}) ${opt}
                 </label>`;
    });
    
    html += '</div>';
    html += `<button class="save-btn" onclick="saveAnswer()">Save Answer</button></div>`;
    questionContainer.innerHTML = html;

    prevBtn.style.display = currentIndex === 0 ? 'none' : 'inline-block';
    nextBtn.style.display = currentIndex === questions.length - 1 ? 'none' : 'inline-block';
    submitBtn.style.display = currentIndex === questions.length - 1 ? 'inline-block' : 'none';
    updateProgress();
}

function saveAnswer() {
    const q = questions[currentIndex];
    const selected = document.querySelector('input[name="option"]:checked');
    if (selected) {
        // Store the letter "A"/"B"/"C"/"D" instead of full option text
        answers[q.id] = selected.dataset.letter;
        localStorage.setItem('exam_answers_' + rollNumber + '_' + testId, JSON.stringify(answers));
        alert("Answer saved!");
    } else {
        alert("Please select an option before saving.");
    }
}



prevBtn.onclick = ()=>{ currentIndex--; renderQuestion(); };
nextBtn.onclick = ()=>{ currentIndex++; renderQuestion(); };

function updateProgress(){
    const percent = ((currentIndex+1)/questions.length)*100;
    progressEl.style.width = percent+'%';
}

submitBtn.onclick = ()=>{ submitExam(); };

function submitExam(){
    modal.style.display = 'block';
    submitBtn.disabled = true;

    const xhr = new XMLHttpRequest();
    xhr.open("POST","submit_exam.php",true);
    xhr.setRequestHeader("Content-Type","application/json;charset=UTF-8");
    xhr.onreadystatechange = ()=> {
        if(xhr.readyState===4){
            modal.style.display = 'none';
            if(xhr.status===200){
                // Replace page content with returned HTML
                document.open();
                document.write(xhr.responseText);
                document.close();
                localStorage.removeItem('exam_answers_' + rollNumber + '_' + testId);
            } else {
                alert("Submission failed! Please try again.");
                submitBtn.disabled = false;
            }
        }
    };

    xhr.send(JSON.stringify({test_id:testId, answers:answers}));
}

// Timer
let totalSeconds = <?= $duration_minutes ?> * 60;
const timerEl = document.getElementById('time');
const timerInterval = setInterval(()=> {
    let mins = Math.floor(totalSeconds/60);
    let secs = totalSeconds%60;
    timerEl.innerText = mins+":" + (secs<10?'0':'') + secs;
    if(totalSeconds<=0){
        clearInterval(timerInterval);
        alert("Time over! Submitting your exam.");
        submitExam();
    }
    totalSeconds--;
},1000);

renderQuestion();
</script>

</body>
</html>
