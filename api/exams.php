<?php
/**
 * Exams API
 * Handles CRUD operations for exams
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

class ExamsAPI {
    private $conn;
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Get all exams
     */
    public function getAllExams() {
        try {
            $query = "
                SELECT 
                    e.*,
                    CONCAT(f.first_name, ' ', f.last_name) as created_by_name
                FROM exams e
                LEFT JOIN faculty f ON e.created_by = f.id
                ORDER BY e.exam_date DESC, e.start_time DESC
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $exams = $stmt->fetchAll();

            return $exams;

        } catch (Exception $e) {
            throw new Exception("Error fetching exams: " . $e->getMessage());
        }
    }

    /**
     * Get exam by ID
     */
    public function getExamById($exam_id) {
        try {
            $query = "
                SELECT 
                    e.*,
                    CONCAT(f.first_name, ' ', f.last_name) as created_by_name
                FROM exams e
                LEFT JOIN faculty f ON e.created_by = f.id
                WHERE e.id = :exam_id
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute(['exam_id' => $exam_id]);
            $exam = $stmt->fetch();

            return $exam;

        } catch (Exception $e) {
            throw new Exception("Error fetching exam: " . $e->getMessage());
        }
    }

    /**
     * Create new exam
     */
    public function createExam($data) {
        try {
            $query = "
                INSERT INTO exams (
                    title, exam_date, start_time, end_time, venue, 
                    subject_code, subject_name, total_students, 
                    status, created_by
                ) VALUES (
                    :title, :exam_date, :start_time, :end_time, :venue,
                    :subject_code, :subject_name, :total_students,
                    :status, :created_by
                )
            ";

            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                'title' => $data['title'],
                'exam_date' => $data['exam_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'venue' => $data['venue'],
                'subject_code' => $data['subject_code'] ?? null,
                'subject_name' => $data['subject_name'] ?? null,
                'total_students' => $data['total_students'] ?? 0,
                'status' => $data['status'] ?? 'scheduled',
                'created_by' => $data['created_by'] ?? 1 // Default admin ID
            ]);

            if ($result) {
                return $this->conn->lastInsertId();
            }

            return false;

        } catch (Exception $e) {
            throw new Exception("Error creating exam: " . $e->getMessage());
        }
    }

    /**
     * Update exam
     */
    public function updateExam($exam_id, $data) {
        try {
            $query = "
                UPDATE exams SET
                    title = :title,
                    exam_date = :exam_date,
                    start_time = :start_time,
                    end_time = :end_time,
                    venue = :venue,
                    subject_code = :subject_code,
                    subject_name = :subject_name,
                    total_students = :total_students,
                    status = :status,
                    updated_at = NOW()
                WHERE id = :exam_id
            ";

            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                'exam_id' => $exam_id,
                'title' => $data['title'],
                'exam_date' => $data['exam_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'venue' => $data['venue'],
                'subject_code' => $data['subject_code'] ?? null,
                'subject_name' => $data['subject_name'] ?? null,
                'total_students' => $data['total_students'] ?? 0,
                'status' => $data['status'] ?? 'scheduled'
            ]);

            return $result;

        } catch (Exception $e) {
            throw new Exception("Error updating exam: " . $e->getMessage());
        }
    }

    /**
     * Delete exam
     */
    public function deleteExam($exam_id) {
        try {
            // First check if there are any invigilator duties assigned
            $query = "SELECT COUNT(*) as count FROM invigilator_duties WHERE exam_id = :exam_id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute(['exam_id' => $exam_id]);
            $result = $stmt->fetch();

            if ($result['count'] > 0) {
                throw new Exception("Cannot delete exam with assigned invigilator duties");
            }

            // Delete exam classes first
            $query = "DELETE FROM exam_classes WHERE exam_id = :exam_id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute(['exam_id' => $exam_id]);

            // Delete exam
            $query = "DELETE FROM exams WHERE id = :exam_id";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute(['exam_id' => $exam_id]);

            return $result;

        } catch (Exception $e) {
            throw new Exception("Error deleting exam: " . $e->getMessage());
        }
    }

    /**
     * Get upcoming exams (for duty assignment)
     */
    public function getUpcomingExams() {
        try {
            $query = "
                SELECT id, title, exam_date, start_time, end_time, venue
                FROM exams 
                WHERE exam_date >= CURDATE() 
                AND status = 'scheduled'
                ORDER BY exam_date ASC, start_time ASC
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $exams = $stmt->fetchAll();

            return $exams;

        } catch (Exception $e) {
            throw new Exception("Error fetching upcoming exams: " . $e->getMessage());
        }
    }
}

// Handle API requests
try {
    $api = new ExamsAPI();
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($method) {
        case 'GET':
            $exam_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
            $type = isset($_GET['type']) ? $_GET['type'] : 'all';

            switch ($type) {
                case 'upcoming':
                    $result = $api->getUpcomingExams();
                    break;
                case 'single':
                    if (!$exam_id) {
                        throw new Exception("Exam ID is required");
                    }
                    $result = $api->getExamById($exam_id);
                    break;
                default:
                    $result = $api->getAllExams();
            }

            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;

        case 'POST':
            if (!$input) {
                throw new Exception("Request data is required");
            }

            $exam_id = $api->createExam($input);

            if ($exam_id) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Exam created successfully',
                    'exam_id' => $exam_id
                ]);
            } else {
                throw new Exception("Failed to create exam");
            }
            break;

        case 'PUT':
            if (!$input || !isset($input['id'])) {
                throw new Exception("Exam ID and data are required");
            }

            $result = $api->updateExam($input['id'], $input);

            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Exam updated successfully' : 'Failed to update exam'
            ]);
            break;

        case 'DELETE':
            $exam_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

            if (!$exam_id) {
                throw new Exception("Exam ID is required");
            }

            $result = $api->deleteExam($exam_id);

            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Exam deleted successfully' : 'Failed to delete exam'
            ]);
            break;

        default:
            throw new Exception("Method not allowed");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
