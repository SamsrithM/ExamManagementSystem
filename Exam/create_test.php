<?php
session_start(); // ✅ Needed to access logged-in faculty info

// --- POST request handler ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get JSON body
    $data = json_decode(file_get_contents('php://input'), true);

    // Database connection using environment variables
    $db_host = getenv('DB_HOST') ?: 'mysql';
    $db_user = getenv('DB_USER') ?: 'root';
    $db_pass = getenv('DB_PASS') ?: '';
    $db_name = getenv('DB_TEST') ?: 'test_creation';

    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        die(json_encode(['status'=>'error','message'=>'Database connection failed.']));
    }

    // Extract form values
    $branch        = $conn->real_escape_string($data['branch'] ?? '');
    $title         = $conn->real_escape_string($data['title'] ?? '');
    $date          = $conn->real_escape_string($data['date'] ?? '');
    $availableFrom = $conn->real_escape_string($data['availableFrom'] ?? '');
    $duration      = (int)($data['duration'] ?? 0);
    $type          = $conn->real_escape_string($data['type'] ?? '');

    // Validate
    if (!$branch || !$title || !$date || !$availableFrom || !$duration || !$type) {
        echo json_encode(['status'=>'error','message'=>'All fields are required.']);
        exit;
    }

    // Check if faculty is logged in
    if (!isset($_SESSION['faculty_user'])) {
        echo json_encode(['status'=>'error','message'=>'Faculty not logged in.']);
        exit;
    }
    $created_by = $conn->real_escape_string($_SESSION['faculty_user']);

    // Insert test
    $sql = "INSERT INTO tests (branch, test_title, test_date, available_from, duration, test_type, created_by)
            VALUES ('$branch','$title','$date','$availableFrom',$duration,'$type','$created_by')";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status'=>'success','test_id'=>$conn->insert_id]);
    } else {
        echo json_encode(['status'=>'error','message'=>'Insert failed: '.$conn->error]);
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
  <title>Create Test</title>
  <style>
  body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f7f9;
    display: flex;
    height: 100vh;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }

  form {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 400px;
  }

  h2 {
    text-align: center;
    color: #1abc9c;
    font-size: 24px;
    margin-bottom: 20px;
  }

  label {
    font-weight: bold;
    display: block;
    margin-bottom: 6px;
    color: #333;
  }

  input,
  select {
    width: 100%;
    padding: 10px;
    margin-bottom: 16px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 15px;
  }

  button {
    background: #1abc9c;
    color: white;
    border: none;
    padding: 12px;
    width: 100%;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s;
    font-size: 16px;
    font-weight: bold;
  }

  button:hover {
    background: #159a85;
  }

  .modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease-in-out;
  }

  .modal-content {
    background: white;
    padding: 25px 30px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
    animation: scaleUp 0.3s ease-in-out;
    max-width: 90%;
  }

  .modal-content h3 {
    margin: 0;
    color: #2ecc71;
    font-size: 20px;
    font-weight: 600;
  }

  .modal-content button {
    margin-top: 15px;
    background: #2ecc71;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.3s;
    font-size: 15px;
    font-weight: bold;
  }

  .modal-content button:hover {
    background: #27ae60;
  }

  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }

  @keyframes scaleUp {
    from { transform: scale(0.8); }
    to { transform: scale(1); }
  }

  @media screen and (max-width: 480px) {
    form {
      padding: 25px 20px;
    }

    h2 {
      font-size: 20px;
    }

    input,
    select {
      font-size: 14px;
      padding: 8px;
    }

    button {
      font-size: 15px;
      padding: 10px;
    }

    .modal-content h3 {
      font-size: 18px;
    }

    .modal-content button {
      font-size: 14px;
      padding: 8px 16px;
    }
  }
</style>
</head>
<body>

<!-- Success Popup -->
<div id="successModal" class="modal">
  <div class="modal-content">
    <h3>✅ Test Created Successfully!</h3>
    <button id="okButton">OK</button>
  </div>
</div>

<form id="createTestForm">
  <h2 style="text-align:center;color:#1abc9c;">Create Test</h2>

  <label>Branch</label>
  <select id="branch" required>
    <option value="">Select Branch</option>
    <option value="CSE">CSE</option>
    <option value="AIDS">AIDS</option>
    <option value="ECE">ECE</option>
    <option value="MECH">MECH</option>
  </select>

  <label>Test Title</label>
  <input type="text" id="testTitle" required>

  <label>Test Date</label>
  <input type="date" id="testDate" required>

  <label>Available From (Time)</label>
  <input type="time" id="availableFrom" required>

  <label>Duration (Minutes)</label>
  <input type="number" id="duration" required min="1" max="300">

  <label>Test Type</label>
  <select id="testType" required>
    <option value="">Select Type</option>
    <option value="Quiz">Quiz</option>
    <option value="Assignment">Assignment</option>
  </select>

  <button type="submit">Create Test</button>
</form>

<script>
const modal = document.getElementById("successModal");
const okButton = document.getElementById("okButton");

document.getElementById('createTestForm').addEventListener('submit', function(e){
  e.preventDefault();

  const formData = {
    branch: document.getElementById('branch').value,
    title: document.getElementById('testTitle').value.trim(),
    date: document.getElementById('testDate').value,
    availableFrom: document.getElementById('availableFrom').value,
    duration: document.getElementById('duration').value,
    type: document.getElementById('testType').value
  };

  fetch('create_test.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(formData)
  })
  .then(res => res.json())
  .then(data => {
    if(data.status === 'success'){
      localStorage.setItem('current_test_id', data.test_id);
      modal.style.display = "flex"; // show popup
    } else {
      alert('❌ ' + data.message);
    }
  })
  .catch(err => alert('⚠️ Error: ' + err));
});

// Close modal and redirect
okButton.addEventListener('click', () => {
  modal.style.display = "none";
  window.location.href = 'Exam/creating_test.php';
});
</script>

</body>
</html>
