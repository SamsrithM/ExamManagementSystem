<?php
session_start();
header('Content-Type: application/json');

// --- Check session ---
if (!isset($_SESSION['faculty_user'])) {
    echo json_encode(['success'=>false, 'message'=>'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$duty_id = intval($data['duty_id'] ?? 0);
$reason = $data['reason'] ?? '';

if ($duty_id <= 0) {
    echo json_encode(['success'=>false, 'message'=>'Invalid duty ID']);
    exit;
}

$faculty_email = $_SESSION['faculty_user'];

// --- Detect environment ---
$is_render = getenv('RENDER') ? true : false;

// DB credentials
$db_host = getenv('DB_HOST') ?: ($is_render ? 'your_postgres_host' : 'localhost');
$db_user = getenv('DB_USER') ?: ($is_render ? 'postgres' : 'root');
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_ROOM') ?: 'room_allocation';

$response = ['success' => false, 'message' => '', 'total_hours' => 0];

if ($is_render) {
    // --- PostgreSQL connection ---
    $conn = pg_connect("host=$db_host dbname=$db_name user=$db_user password=$db_pass");
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Postgres connection failed']);
        exit;
    }

    if ($action === 'ok') {
        // Update status
        pg_prepare($conn, "update_status", "UPDATE faculty_assignments SET status='confirmed' WHERE id=$1 AND email_id=$2");
        pg_execute($conn, "update_status", [$duty_id, $faculty_email]);

        // Increase hours_of_duty_done
        pg_query_params($conn, "UPDATE faculty_duty_done SET hours_of_duty_done = hours_of_duty_done + 1 WHERE email_id = $1", [$faculty_email]);

        $response['success'] = true;
        $response['message'] = 'Duty confirmed successfully!';
    }
    elseif ($action === 'cancel') {
        if (empty($reason)) {
            echo json_encode(['success'=>false, 'message'=>'Reason required']);
            exit;
        }

        // Create cancel_duty table if not exists
        pg_query($conn, "
            CREATE TABLE IF NOT EXISTS cancel_duty (
                id SERIAL PRIMARY KEY,
                duty_id INT NOT NULL,
                email_id VARCHAR(255) NOT NULL,
                reason TEXT NOT NULL,
                cancelled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Insert cancel reason
        pg_query_params($conn, "INSERT INTO cancel_duty (duty_id,email_id,reason) VALUES ($1,$2,$3)", [$duty_id, $faculty_email, $reason]);

        // Delete duty
        pg_query_params($conn, "DELETE FROM faculty_assignments WHERE id=$1 AND email_id=$2", [$duty_id, $faculty_email]);

        $response['success'] = true;
        $response['message'] = 'Duty cancelled successfully!';
    }
    else {
        echo json_encode(['success'=>false, 'message'=>'Invalid action']);
        exit;
    }

    // Fetch total hours
    $res_hours = pg_query_params($conn, "SELECT SUM(hours_of_duty_done) as total_hours FROM faculty_duty_done WHERE email_id=$1", [$faculty_email]);
    $row_hours = pg_fetch_assoc($res_hours);
    $response['total_hours'] = $row_hours['total_hours'] ?? 0;

    pg_close($conn);

} else {
    // --- MySQL connection ---
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'DB connection failed']);
        exit;
    }

    if ($action === 'ok') {
        $stmt = $conn->prepare("UPDATE faculty_assignments SET status='confirmed' WHERE id=? AND email_id=?");
        $stmt->bind_param("is", $duty_id, $faculty_email);
        $stmt->execute();
        $stmt->close();

        $conn->query("UPDATE faculty_duty_done SET hours_of_duty_done = hours_of_duty_done + 1 WHERE email_id = '$faculty_email'");

        $response['success'] = true;
        $response['message'] = 'Duty confirmed successfully!';
    }
    elseif ($action === 'cancel') {
        if (empty($reason)) {
            echo json_encode(['success'=>false, 'message'=>'Reason required']);
            exit;
        }

        $conn->query("
            CREATE TABLE IF NOT EXISTS cancel_duty (
                id INT AUTO_INCREMENT PRIMARY KEY,
                duty_id INT NOT NULL,
                email_id VARCHAR(255) NOT NULL,
                reason TEXT NOT NULL,
                cancelled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $stmt = $conn->prepare("INSERT INTO cancel_duty (duty_id,email_id,reason) VALUES (?,?,?)");
        $stmt->bind_param("iss", $duty_id, $faculty_email, $reason);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM faculty_assignments WHERE id=? AND email_id=?");
        $stmt->bind_param("is", $duty_id, $faculty_email);
        $stmt->execute();
        $stmt->close();

        $response['success'] = true;
        $response['message'] = 'Duty cancelled successfully!';
    }
    else {
        echo json_encode(['success'=>false, 'message'=>'Invalid action']);
        exit;
    }

    // Fetch total hours
    $stmt_hours = $conn->prepare("SELECT SUM(hours_of_duty_done) as total_hours FROM faculty_duty_done WHERE email_id=?");
    $stmt_hours->bind_param("s", $faculty_email);
    $stmt_hours->execute();
    $res_hours = $stmt_hours->get_result();
    $row_hours = $res_hours->fetch_assoc();
    $response['total_hours'] = $row_hours['total_hours'] ?? 0;
    $stmt_hours->close();

    $conn->close();
}

// --- Output ---
echo json_encode($response);
?>
