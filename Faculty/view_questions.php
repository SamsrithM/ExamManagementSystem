<?php
session_start();

// Detect environment
$is_render = getenv('RENDER') ? true : false;

// DB credentials
$db_host = getenv('DB_HOST') ?: ($is_render ? 'your_postgres_host' : 'localhost');
$db_user = getenv('DB_USER') ?: ($is_render ? 'postgres' : 'root');
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_TEST') ?: 'test_creation';

// Connect to DB
if ($is_render) {
    $conn = pg_connect("host=$db_host dbname=$db_name user=$db_user password=$db_pass");
    if (!$conn) die("<h2 style='color:red;'>PostgreSQL connection failed</h2>");
} else {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) die("<h2 style='color:red;'>MySQL connection failed: " . $conn->connect_error . "</h2>");
}

$test_id = isset($_GET['test_id']) ? (int)$_GET['test_id'] : 0;

// Fetch test details
$test = [];
if ($is_render) {
    $res = pg_prepare($conn, "get_test", "SELECT * FROM tests WHERE test_id=$1");
    $res = pg_execute($conn, "get_test", [$test_id]);
    $test = pg_fetch_assoc($res);
} else {
    $stmt = $conn->prepare("SELECT * FROM tests WHERE test_id = ?");
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $test = $result->fetch_assoc();
    $stmt->close();
}

// Fetch questions
$questions = [];
if ($is_render) {
    $res_q = pg_prepare($conn, "get_questions", "SELECT * FROM questions WHERE test_id=$1");
    $res_q = pg_execute($conn, "get_questions", [$test_id]);
    while ($row = pg_fetch_assoc($res_q)) $questions[] = $row;
} else {
    $stmt_q = $conn->prepare("SELECT * FROM questions WHERE test_id = ?");
    $stmt_q->bind_param("i", $test_id);
    $stmt_q->execute();
    $result_q = $stmt_q->get_result();
    while ($row = $result_q->fetch_assoc()) $questions[] = $row;
    $stmt_q->close();
}

if (!$is_render) $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Questions - <?php echo htmlspecialchars($test['test_title']); ?></title>
<style>
/* Add your CSS styling here (same as original) */
body { font-family: Arial, sans-serif; padding: 20px; background: #f4f7f9; }
h2 { text-align: center; color: #1abc9c; margin-bottom: 5px; }
h3 { text-align: center; color: #34495e; margin-bottom: 20px; }
.table-wrapper { overflow-x:auto; }
table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; vertical-align: top; }
th { background-color: #1abc9c; color: white; }
tbody tr:nth-child(even) { background: #f9f9f9; }
a.button { padding: 6px 12px; background: #1abc9c; color: white; text-decoration: none; border-radius: 5px; }
a.button:hover { background: #159a85; }
ul.options { padding-left: 20px; margin: 0; }
ul.options li { margin: 2px 0; }
.correct { color: green; font-weight: bold; }
</style>
</head>
<body>


<h2>Test: <?php echo htmlspecialchars($test['test_title']); ?> (ID: <?php echo htmlspecialchars($test['test_id']); ?>)</h2>
<h3>Branch: <?php echo htmlspecialchars($test['branch']); ?> | Date: <?php echo htmlspecialchars($test['test_date']); ?> | Created By: <?php echo htmlspecialchars($test['created_by']); ?></h3>

<div class="table-wrapper">
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
            <td><?php echo htmlspecialchars($q['id']); ?></td>
            <td><?php echo htmlspecialchars($q['question_text']); ?></td>
            <td><?php echo htmlspecialchars($q['question_type']); ?></td>
            <td>
                <?php 
                $options = json_decode($q['options'], true);
                if($options && is_array($options)) {
                    echo '<ul class="options">';
                    foreach($options as $opt) {
                        echo '<li>'.htmlspecialchars($opt).'</li>';
                    }
                    echo '</ul>';
                } else {
                    echo htmlspecialchars($q['options']);
                }
                ?>
            </td>
            <td class="correct"><?php echo htmlspecialchars($q['correct_answer']); ?></td>
            <td><?php echo htmlspecialchars($q['descriptive_answer']); ?></td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="6" style="text-align:center;">No questions found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

<p style="text-align:center;margin-top:20px;">
    <a class="button" href="view_tests.php">← Back to Tests</a>
</p>

</body>
</html>

<?php 
$stmt_q->close();
$conn->close();
?>
