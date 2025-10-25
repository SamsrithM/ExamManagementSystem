<?php
// reviewquestions.php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "test_creation";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Database connection failed: ".$conn->connect_error);
}

// Fetch latest test_id from questions table
$result = $conn->query("SELECT DISTINCT test_id FROM questions ORDER BY test_id DESC LIMIT 1");
if ($result->num_rows == 0) {
    die("<h2>No questions found in database.</h2>");
}
$row = $result->fetch_assoc();
$test_id = $row['test_id'];

// Fetch questions for this test_id
$stmt = $conn->prepare("SELECT * FROM questions WHERE test_id = ?");
$stmt->bind_param("i", $test_id);
$stmt->execute();
$res = $stmt->get_result();

$questions = [];
while($row = $res->fetch_assoc()) {
    $questions[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Review Questions - Faculty Dashboard</title>
<style>
  body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f9fafb;
    padding: 0;
  }

  header {
    background-color: #1f3c88;
    color: white;
    padding: 20px;
    text-align: center;
    font-size: 1.8rem;
    font-weight: 700;
    letter-spacing: 1px;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
  }

  main {
    max-width: 900px;
    margin: 30px auto;
    padding: 0 20px 50px 20px;
  }

  h2 {
    color: #1f3c88;
    text-align: center;
    margin-bottom: 40px;
    font-size: 1.6rem;
  }

  .question-block {
    background: white;
    border-radius: 12px;
    padding: 20px 25px;
    margin-bottom: 25px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  }

  .question-header {
    font-weight: 700;
    margin-bottom: 15px;
    font-size: 1.1rem;
    color: #333;
  }

  .options-list {
    list-style-type: none;
    padding-left: 0;
    margin-top: 10px;
  }

  .options-list li {
    margin-bottom: 8px;
    padding: 8px 12px;
    background: #f1f3f6;
    border-radius: 6px;
    font-size: 0.95rem;
  }

  .correct-answer {
    font-weight: 700;
    color: #27ae60;
    margin-top: 12px;
  }

  .button-group {
    text-align: center;
    margin-top: 30px;
  }

  .button-group button {
    padding: 12px 25px;
    font-size: 1rem;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    margin: 0 10px;
    transition: background-color 0.3s ease;
  }

  .button-submit {
    background-color: #27ae60;
    color: white;
  }

  .button-submit:hover {
    background-color: #1e8449;
  }

  .button-back {
    background-color: #2980b9;
    color: white;
  }

  .button-back:hover {
    background-color: #1c5980;
  }

  .modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    align-items: center;
    justify-content: center;
    z-index: 1000;
  }

  .modal-content {
    background: white;
    padding: 30px 50px;
    border-radius: 12px;
    text-align: center;
    font-size: 1.3rem;
    font-weight: bold;
    color: #27ae60;
    max-width: 90%;
  }

  .modal-content button {
    margin-top: 20px;
    padding: 10px 25px;
    border: none;
    background: #27ae60;
    color: white;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    font-size: 1rem;
  }

  .modal-content button:hover {
    background: #1e8449;
  }

  @media screen and (max-width: 768px) {
    h2 {
      font-size: 1.4rem;
    }

    .question-block {
      padding: 15px 20px;
    }

    .question-header {
      font-size: 1rem;
    }

    .options-list li {
      font-size: 0.9rem;
    }

    .modal-content {
      font-size: 1.1rem;
      padding: 25px 30px;
    }

    .modal-content button {
      font-size: 0.95rem;
      padding: 8px 20px;
    }
  }

  @media screen and (max-width: 480px) {
    header {
      font-size: 1.5rem;
      padding: 15px;
    }

    h2 {
      font-size: 1.2rem;
    }

    .button-group button {
      font-size: 0.95rem;
      padding: 10px 20px;
      margin: 5px;
    }

    .modal-content {
      font-size: 1rem;
      padding: 20px;
    }

    .modal-content button {
      font-size: 0.9rem;
      padding: 8px 16px;
    }
  }
</style>
</head>
<body>

<header>Review Questions</header>

<main>
    <h2>Review Your Questions</h2>

    <?php foreach($questions as $index => $q): ?>
        <div class="question-block">
            <div class="question-header">Question <?php echo $index + 1; ?></div>
            <p><strong>Question:</strong> <?php echo htmlspecialchars($q['question_text']); ?></p>
            <p><strong>Type:</strong> <?php echo ucfirst($q['question_type']); ?></p>

            <?php if($q['question_type'] == 'objective' && $q['options']): 
                $opts = json_decode($q['options'], true);
            ?>
                <ul class="options-list">
                    <?php foreach($opts as $i => $opt): 
                        $letter = chr(65+$i); ?>
                        <li>Option <?php echo $letter; ?>: <?php echo htmlspecialchars($opt); ?></li>
                    <?php endforeach; ?>
                </ul>
                <p class="correct-answer">Correct Answer: <?php echo $q['correct_answer']; ?></p>
            <?php elseif($q['question_type'] == 'descriptive'): ?>
                <p><strong>Answer:</strong> <?php echo htmlspecialchars($q['descriptive_answer']); ?></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="button-group">
        <button class="button-back" onclick="window.location.href='update_questions.php'">Back to Edit</button>
        <button class="button-submit" onclick="showModal()">Submit</button>
    </div>
</main>

<!-- Success Modal -->
<div id="successModal" class="modal">
    <div class="modal-content">
        ✅ Questions Submitted Successfully!
        <br>
        <button onclick="redirectDashboard()">OK</button>
    </div>
</div>

<script>
function showModal() {
    document.getElementById('successModal').style.display = 'flex';
}

// Redirect to dashboard
function redirectDashboard() {
    window.location.href = 'Faculty/faculty_front_page.php'; // change to your dashboard path
}

// Optional: auto-redirect after 3 seconds
// setTimeout(redirectDashboard, 3000);
</script>

</body>
</html>
