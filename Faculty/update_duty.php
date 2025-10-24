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

// --- DB Connection ---
$conn = new mysqli("localhost", "root", "", "room_allocation");
if ($conn->connect_error) {
    echo json_encode(['success'=>false, 'message'=>'DB connection failed']);
    exit;
}

$response = ['success' => false, 'message' => '', 'total_hours' => 0];

// --- OK ACTION ---
// --- OK ACTION ---
if ($action === 'ok') {

    $email_id = $faculty_email;

    // ✅ Update status to confirmed in faculty_assignments
    $update_status = $conn->prepare("UPDATE faculty_assignments SET status = 'confirmed' WHERE id = ? AND email_id = ?");
    $update_status->bind_param("is", $duty_id, $email_id);
    $update_status->execute();
    $update_status->close();

    // ✅ Increase hours_of_duty_done
    $update_query = "UPDATE faculty_duty_done 
                     SET hours_of_duty_done = hours_of_duty_done + 1 
                     WHERE email_id = '$email_id'";
    $conn->query($update_query);

    if ($conn->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Duty confirmed successfully!';
    } else {
        $response['success'] = false;
        $response['message'] = 'No faculty record found to update hours';
    }
}

// --- CANCEL ACTION ---
elseif ($action === 'cancel') {

    if (empty($reason)) {
        echo json_encode(['success'=>false, 'message'=>'Reason required']);
        exit;
    }

    // ✅ Create cancel_duty table if not exists
    $conn->query("
        CREATE TABLE IF NOT EXISTS cancel_duty (
            id INT AUTO_INCREMENT PRIMARY KEY,
            duty_id INT NOT NULL,
            email_id VARCHAR(255) NOT NULL,
            reason TEXT NOT NULL,
            cancelled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // ✅ Insert cancel reason
    $stmt = $conn->prepare("INSERT INTO cancel_duty (duty_id, email_id, reason) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $duty_id, $faculty_email, $reason);
    $stmt->execute();
    $stmt->close();

    // ✅ Delete duty from faculty_assignments
    $stmt = $conn->prepare("DELETE FROM faculty_assignments WHERE id = ? AND email_id = ?");
    $stmt->bind_param("is", $duty_id, $faculty_email);
    $stmt->execute();
    $stmt->close();

    $response['success'] = true;
    $response['message'] = 'Duty cancelled successfully!';
}
// --- INVALID ACTION ---
else {
    echo json_encode(['success'=>false, 'message'=>'Invalid action']);
    exit;
}

// --- Fetch updated total hours ---
$stmt_hours = $conn->prepare("SELECT SUM(hours_of_duty_done) as total_hours FROM faculty_duty_done WHERE email_id = ?");
$stmt_hours->bind_param("s", $faculty_email);
$stmt_hours->execute();
$res_hours = $stmt_hours->get_result();
$row_hours = $res_hours->fetch_assoc();
$response['total_hours'] = $row_hours['total_hours'] ?? 0;
$stmt_hours->close();

$conn->close();

// --- Final Output ---
echo json_encode($response);
?>
