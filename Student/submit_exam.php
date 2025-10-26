<?php
session_start();

// --- Check login ---
if (!isset($_SESSION['roll_number'])) {
    echo "<h2 style='text-align:center;color:red;margin-top:50px;'>Student not logged in!</h2>";
    exit;
}

$roll_number = $_SESSION['roll_number'];

// --- Get JSON input ---
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo "<h2 style='text-align:center;color:red;margin-top:50px;'>Invalid submission!</h2>";
    exit;
}

$test_id = intval($data['test_id']);
$answers = $data['answers'] ?? [];

if ($test_id <= 0) {
    echo "<h2 style='text-align:center;color:red;margin-top:50px;'>Invalid exam ID!</h2>";
    exit;
}

// --- Environment variables ---
$db_type = getenv('DB_TYPE') ?: 'mysql'; // "mysql" or "pgsql"
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_TEST') ?: 'test_creation';

$total_marks = 0;
$marks_obtained = 0;
$questions = [];

// --- MySQL mode ---
if ($db_type === 'mysql') {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) die("<h2 style='text-align:center;color:red;margin-top:50px;'>DB connection failed: ".$conn->connect_error."</h2>");

    // Create marks_awarded table if not exists
    $conn->query("
    CREATE TABLE IF NOT EXISTS marks_awarded (
        roll_number VARCHAR(50),
        test_id INT,
        marks_obtained INT,
        total_marks INT,
        PRIMARY KEY(roll_number, test_id)
    ) ENGINE=InnoDB;
    ");

    // Fetch questions
    $stmt = $conn->prepare("SELECT id, question_type, correct_answer FROM questions WHERE test_id=?");
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $questions[$row['id']] = $row;
        if ($row['question_type'] === 'objective') $total_marks++;
    }
    $stmt->close();

    // Calculate marks
    foreach ($answers as $qid => $selectedLetter) {
        $qid = intval($qid);
        if (!isset($questions[$qid])) continue;
        $q = $questions[$qid];
        if ($q['question_type'] === 'objective' && strtoupper(trim($selectedLetter)) === strtoupper(trim($q['correct_answer']))) {
            $marks_obtained++;
        }
    }

    // Insert/update marks
    $stmt = $conn->prepare("
        INSERT INTO marks_awarded (roll_number, test_id, marks_obtained, total_marks)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE marks_obtained=?, total_marks=?
    ");
    $stmt->bind_param("siiiii", $roll_number, $test_id, $marks_obtained, $total_marks, $marks_obtained, $total_marks);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

// --- PostgreSQL mode ---
elseif ($db_type === 'pgsql') {
    $conn_string = "host=$db_host dbname=$db_name user=$db_user password=$db_pass";
    $conn = pg_connect($conn_string);
    if (!$conn) die("<h2 style='text-align:center;color:red;margin-top:50px;'>PostgreSQL connection failed.</h2>");

    // Create table if not exists
    pg_query($conn, "
    CREATE TABLE IF NOT EXISTS marks_awarded (
        roll_number VARCHAR(50),
        test_id INT,
        marks_obtained INT,
        total_marks INT,
        PRIMARY KEY(roll_number, test_id)
    );
    ");

    // Fetch questions
    $res = pg_prepare($conn, "get_questions", "SELECT id, question_type, correct_answer FROM questions WHERE test_id=$1");
    $res = pg_execute($conn, "get_questions", [$test_id]);
    while ($row = pg_fetch_assoc($res)) {
        $questions[$row['id']] = $row;
        if ($row['question_type'] === 'objective') $total_marks++;
    }
    pg_free_result($res);

    // Calculate marks
    foreach ($answers as $qid => $selectedLetter) {
        $qid = intval($qid);
        if (!isset($questions[$qid])) continue;
        $q = $questions[$qid];
        if ($q['question_type'] === 'objective' && strtoupper(trim($selectedLetter)) === strtoupper(trim($q['correct_answer']))) {
            $marks_obtained++;
        }
    }

    // Insert or update marks
    $upsert_query = "
    INSERT INTO marks_awarded (roll_number, test_id, marks_obtained, total_marks)
    VALUES ($1,$2,$3,$4)
    ON CONFLICT (roll_number, test_id) DO UPDATE SET marks_obtained=$3, total_marks=$4;
    ";
    $res = pg_prepare($conn, "upsert_marks", $upsert_query);
    $res = pg_execute($conn, "upsert_marks", [$roll_number, $test_id, $marks_obtained, $total_marks]);
    pg_close($conn);
}
?>


<!DOCTYPE html>
<html>

<head>
    <title>Exam Submitted</title>
    <style>
body {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    font-family: Arial, sans-serif;
    background-color: #f0f2f5;
    margin: 0;
}
.result-box {
    text-align: center;
    padding: 30px 50px;
    background-color: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    animation: fadeIn 0.8s ease forwards;
    opacity: 0;
    transform: translateY(20px);
}
.result-box h1 {
    color: #28a745;
    margin-bottom: 20px;
}
.dashboard-btn {
    margin-top: 20px;
    padding: 10px 25px;
    background-color: #007bff;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    transition: background-color 0.3s ease, transform 0.3s ease;
}
.dashboard-btn:hover {
    background-color: #0056b3;
    transform: scale(1.05);
}
@keyframes fadeIn {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
</head>
<body>
    <div class="result-box">
        <h1>Exam submitted successfully!</h1>
        <a href="student_front_page.php" class="dashboard-btn">Return to Dashboard</a>
    </div>
</body>
</html>
