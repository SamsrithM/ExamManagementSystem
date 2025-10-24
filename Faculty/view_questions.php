<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db   = "test_creation";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$test_id = isset($_GET['test_id']) ? (int)$_GET['test_id'] : 0;

// Fetch test details
$test_result = $conn->query("SELECT * FROM tests WHERE test_id = $test_id");
$test = $test_result->fetch_assoc();

// Fetch questions
$question_result = $conn->query("SELECT * FROM questions WHERE test_id = $test_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Questions</title>
<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f4f7f9; }
h2 { text-align: center; color: #1abc9c; margin-bottom: 10px; }
h3 { text-align: center; color: #34495e; margin-bottom: 20px; }
table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
th { background-color: #1abc9c; color: white; }
a.button { padding: 6px 12px; background: #1abc9c; color: white; text-decoration: none; border-radius: 5px; }
a.button:hover { background: #159a85; }
</style>
</head>
<body>

<h2>Test: <?php echo $test['test_title']; ?> (ID: <?php echo $test['test_id']; ?>)</h2>
<h3>Branch: <?php echo $test['branch']; ?> | Date: <?php echo $test['test_date']; ?> | Created By: <?php echo $test['created_by']; ?></h3>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Question Text</th>
            <th>Type</th>
            <th>Options</th>
            <th>Correct Answer</th>
            <th>Descriptive Answer</th>
        </tr>
    </thead>
    <tbody>
    <?php if($question_result->num_rows > 0): ?>
        <?php while($q = $question_result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $q['id']; ?></td>
            <td><?php echo $q['question_text']; ?></td>
            <td><?php echo $q['question_type']; ?></td>
            <td><?php echo $q['options']; ?></td>
            <td><?php echo $q['correct_answer']; ?></td>
            <td><?php echo $q['descriptive_answer']; ?></td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="6" style="text-align:center;">No questions found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<p style="text-align:center;margin-top:20px;">
    <a class="button" href="view_tests.php">← Back to Tests</a>
</p>

</body>
</html>

<?php $conn->close(); ?>
