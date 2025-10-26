<?php
session_start(); // ✅ Needed to access logged-in faculty info

// Redirect if faculty not logged in
if (!isset($_SESSION['faculty_user'])) {
    header("Location: ../Faculty/faculty_login.php");
    exit;
}

// Database connection using environment variables
$db_host = getenv('DB_HOST') ?: 'mysql';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_TEST') ?: 'test_creation';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("<h2 style='color:red;'>Database connection failed: " . $conn->connect_error . "</h2>");
}

$showModal = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_id = $_POST['test_id'];
    $questions = $_POST['question'];
    $types = $_POST['question_type'];
    $options_data = $_POST['options'] ?? [];
    $correct_answers = $_POST['correct_answer'] ?? [];
    $descriptive_answers = $_POST['descriptive_answer'] ?? [];

    foreach ($questions as $id => $qtext) {
        $qtype = $types[$id];
        if ($qtype === 'objective') {
            $options_json = json_encode(array_map('trim', explode("\n", $options_data[$id])));
            $correct = $correct_answers[$id] ?? '';
            $stmt = $conn->prepare("UPDATE questions SET question_text=?, options=?, correct_answer=? WHERE id=?");
            $stmt->bind_param("sssi", $qtext, $options_json, $correct, $id);
        } else {
            $desc_answer = $descriptive_answers[$id] ?? '';
            $stmt = $conn->prepare("UPDATE questions SET question_text=?, descriptive_answer=? WHERE id=?");
            $stmt->bind_param("ssi", $qtext, $desc_answer, $id);
        }
        $stmt->execute();
        $stmt->close();
    }

    $showModal = true; // show success modal after submit
}

// Fetch latest test_id
$result = $conn->query("SELECT DISTINCT test_id FROM questions ORDER BY test_id DESC LIMIT 1");
if (!$result || $result->num_rows == 0) {
    die("<h2>No questions found in database.</h2>");
}
$row = $result->fetch_assoc();
$test_id = $row['test_id'];

// Fetch questions for editing
$stmt = $conn->prepare("SELECT * FROM questions WHERE test_id = ?");
$stmt->bind_param("i", $test_id);
$stmt->execute();
$res = $stmt->get_result();

$questions = [];
while ($row = $res->fetch_assoc()) {
    $questions[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Update Questions - Faculty Dashboard</title>
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
    transition: transform 0.2s;
  }

  .question-block:hover {
    transform: translateY(-3px);
  }

  .question-header {
    font-weight: 700;
    margin-bottom: 15px;
    font-size: 1.1rem;
    color: #333;
  }

  textarea,
  input[type="text"] {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    margin-bottom: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 0.95rem;
  }

  label {
    font-weight: 600;
    color: #333;
    display: block;
    margin-top: 10px;
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
    margin: 10px 5px;
    transition: background-color 0.3s ease;
  }

  .button-submit {
    background-color: #27ae60;
    color: white;
  }

  .button-submit:hover {
    background-color: #1e8449;
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

    textarea,
    input[type="text"] {
      font-size: 0.9rem;
      padding: 8px;
    }

    .button-group button {
      font-size: 0.95rem;
      padding: 10px 20px;
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
      font-size: 0.9rem;
      padding: 10px 18px;
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

<header>Update Questions</header>

<main>
    <h2>Edit Questions</h2>

    <form method="POST">
        <input type="hidden" name="test_id" value="<?php echo $test_id; ?>">

        <?php foreach($questions as $index => $q): ?>
            <div class="question-block">
                <div class="question-header">Question <?php echo $index + 1; ?></div>
                <input type="hidden" name="question_type[<?php echo $q['id']; ?>]" value="<?php echo $q['question_type']; ?>">
                
                <label><strong>Question:</strong></label>
                <textarea name="question[<?php echo $q['id']; ?>]" required><?php echo htmlspecialchars($q['question_text']); ?></textarea>

                <?php if($q['question_type'] == 'objective'): 
                    $opts = json_decode($q['options'], true);
                    $opts_text = implode("\n", $opts);
                ?>
                    <label><strong>Options (one per line):</strong></label>
                    <textarea name="options[<?php echo $q['id']; ?>]" required><?php echo htmlspecialchars($opts_text); ?></textarea>

                    <label><strong>Correct Answer (Option Letter):</strong></label>
                    <input type="text" name="correct_answer[<?php echo $q['id']; ?>]" value="<?php echo htmlspecialchars($q['correct_answer']); ?>" required>

                <?php elseif($q['question_type'] == 'descriptive'): ?>
                    <label><strong>Answer:</strong></label>
                    <textarea name="descriptive_answer[<?php echo $q['id']; ?>]" required><?php echo htmlspecialchars($q['descriptive_answer']); ?></textarea>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="button-group">
            <button type="submit" class="button-submit">Update Questions</button>
            <button type="button" class="button-submit" onclick="window.location.href='reviewquestions.php'">Back to Review</button>
        </div>
    </form>
</main>

<!-- Success Modal -->
<div id="successModal" class="modal">
    <div class="modal-content">
        ✅ Questions Updated Successfully!
        <br>
        <button onclick="closeModal()">OK</button>
    </div>
</div>

<script>
function closeModal() {
    document.getElementById('successModal').style.display = 'none';
}

// Show modal if PHP sets $showModal
<?php if($showModal): ?>
document.getElementById('successModal').style.display = 'flex';
<?php endif; ?>
</script>

</body>
</html>
    
