<?php
/**
 * Invigilator Duties API
 * Handles CRUD operations for invigilator duties
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

class InvigilatorDutiesAPI {
    private $conn;
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Get invigilator duties for a specific faculty member
     */
    public function getInvigilatorDuties($faculty_id = null) {
        try {
            $query = "
                SELECT 
                    id.id,
                    id.exam_id,
                    e.title,
                    e.exam_date,
                    e.start_time,
                    e.end_time,
                    e.venue,
                    e.subject_code,
                    e.subject_name,
                    id.duty_date,
                    id.status,
                    id.attendance_marked_at,
                    id.notes,
                    CONCAT(f.first_name, ' ', f.last_name) as faculty_name,
                    f.institute_email as faculty_email
                FROM invigilator_duties id
                JOIN exams e ON id.exam_id = e.id
                JOIN faculty f ON id.faculty_id = f.id
                WHERE 1=1
            ";

            $params = [];

            if ($faculty_id) {
                $query .= " AND id.faculty_id = :faculty_id";
                $params['faculty_id'] = $faculty_id;
            }

            $query .= " ORDER BY id.duty_date DESC, e.start_time DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $duties = $stmt->fetchAll();

            // Get other invigilators for each duty
            foreach ($duties as &$duty) {
                $duty['other_invigilators'] = $this->getOtherInvigilators($duty['exam_id'], $duty['faculty_id']);
                $duty['classes'] = $this->getExamClasses($duty['exam_id']);
            }

            return $duties;

        } catch (Exception $e) {
            throw new Exception("Error fetching invigilator duties: " . $e->getMessage());
        }
    }

    /**
     * Get upcoming duties (within next 7 days)
     */
    public function getUpcomingDuties($faculty_id = null) {
        try {
            $query = "
                SELECT 
                    id.id,
                    id.exam_id,
                    e.title,
                    e.exam_date,
                    e.start_time,
                    e.end_time,
                    e.venue,
                    e.subject_code,
                    e.subject_name,
                    id.duty_date,
                    id.status,
                    id.attendance_marked_at,
                    id.notes,
                    CONCAT(f.first_name, ' ', f.last_name) as faculty_name,
                    f.institute_email as faculty_email
                FROM invigilator_duties id
                JOIN exams e ON id.exam_id = e.id
                JOIN faculty f ON id.faculty_id = f.id
                WHERE id.duty_date >= CURDATE() 
                AND id.duty_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                AND id.status IN ('assigned', 'confirmed')
            ";

            $params = [];

            if ($faculty_id) {
                $query .= " AND id.faculty_id = :faculty_id";
                $params['faculty_id'] = $faculty_id;
            }

            $query .= " ORDER BY id.duty_date ASC, e.start_time ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $duties = $stmt->fetchAll();

            // Get other invigilators and classes for each duty
            foreach ($duties as &$duty) {
                $duty['other_invigilators'] = $this->getOtherInvigilators($duty['exam_id'], $duty['faculty_id']);
                $duty['classes'] = $this->getExamClasses($duty['exam_id']);
            }

            return $duties;

        } catch (Exception $e) {
            throw new Exception("Error fetching upcoming duties: " . $e->getMessage());
        }
    }

    /**
     * Get past duties
     */
    public function getPastDuties($faculty_id = null) {
        try {
            $query = "
                SELECT 
                    id.id,
                    id.exam_id,
                    e.title,
                    e.exam_date,
                    e.start_time,
                    e.end_time,
                    e.venue,
                    e.subject_code,
                    e.subject_name,
                    id.duty_date,
                    id.status,
                    id.attendance_marked_at,
                    id.notes,
                    CONCAT(f.first_name, ' ', f.last_name) as faculty_name,
                    f.institute_email as faculty_email
                FROM invigilator_duties id
                JOIN exams e ON id.exam_id = e.id
                JOIN faculty f ON id.faculty_id = f.id
                WHERE id.duty_date < CURDATE()
                OR (id.duty_date = CURDATE() AND e.end_time < CURTIME())
            ";

            $params = [];

            if ($faculty_id) {
                $query .= " AND id.faculty_id = :faculty_id";
                $params['faculty_id'] = $faculty_id;
            }

            $query .= " ORDER BY id.duty_date DESC, e.start_time DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $duties = $stmt->fetchAll();

            // Get other invigilators and classes for each duty
            foreach ($duties as &$duty) {
                $duty['other_invigilators'] = $this->getOtherInvigilators($duty['exam_id'], $duty['faculty_id']);
                $duty['classes'] = $this->getExamClasses($duty['exam_id']);
            }

            return $duties;

        } catch (Exception $e) {
            throw new Exception("Error fetching past duties: " . $e->getMessage());
        }
    }

    /**
     * Get other invigilators for a specific exam
     */
    private function getOtherInvigilators($exam_id, $current_faculty_id) {
        try {
            $query = "
                SELECT CONCAT(f.first_name, ' ', f.last_name) as name
                FROM invigilator_duties id
                JOIN faculty f ON id.faculty_id = f.id
                WHERE id.exam_id = :exam_id 
                AND id.faculty_id != :current_faculty_id
                ORDER BY f.first_name, f.last_name
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                'exam_id' => $exam_id,
                'current_faculty_id' => $current_faculty_id
            ]);

            return $stmt->fetchAll(PDO::FETCH_COLUMN);

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get classes for a specific exam
     */
    private function getExamClasses($exam_id) {
        try {
            $query = "
                SELECT 
                    CONCAT(class_name, ' (', subject_code, ' - ', student_count, ' students)') as class_info
                FROM exam_classes
                WHERE exam_id = :exam_id
                ORDER BY class_name
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute(['exam_id' => $exam_id]);

            return $stmt->fetchAll(PDO::FETCH_COLUMN);

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Update duty status (mark attendance)
     */
    public function updateDutyStatus($duty_id, $status, $notes = '') {
        try {
            $query = "
                UPDATE invigilator_duties 
                SET status = :status, 
                    notes = :notes,
                    attendance_marked_at = NOW(),
                    updated_at = NOW()
                WHERE id = :duty_id
            ";

            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                'status' => $status,
                'notes' => $notes,
                'duty_id' => $duty_id
            ]);

            return $result;

        } catch (Exception $e) {
            throw new Exception("Error updating duty status: " . $e->getMessage());
        }
    }

    /**
     * Get duty details by ID
     */
    public function getDutyById($duty_id) {
        try {
            $query = "
                SELECT 
                    id.*,
                    e.title,
                    e.exam_date,
                    e.start_time,
                    e.end_time,
                    e.venue,
                    e.subject_code,
                    e.subject_name,
                    CONCAT(f.first_name, ' ', f.last_name) as faculty_name
                FROM invigilator_duties id
                JOIN exams e ON id.exam_id = e.id
                JOIN faculty f ON id.faculty_id = f.id
                WHERE id.id = :duty_id
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute(['duty_id' => $duty_id]);
            $duty = $stmt->fetch();

            if ($duty) {
                $duty['other_invigilators'] = $this->getOtherInvigilators($duty['exam_id'], $duty['faculty_id']);
                $duty['classes'] = $this->getExamClasses($duty['exam_id']);
            }

            return $duty;

        } catch (Exception $e) {
            throw new Exception("Error fetching duty details: " . $e->getMessage());
        }
    }
}

// Handle API requests
try {
    $api = new InvigilatorDutiesAPI();
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($method) {
        case 'GET':
            $faculty_id = isset($_GET['faculty_id']) ? (int)$_GET['faculty_id'] : null;
            $type = isset($_GET['type']) ? $_GET['type'] : 'all';

            switch ($type) {
                case 'upcoming':
                    $result = $api->getUpcomingDuties($faculty_id);
                    break;
                case 'past':
                    $result = $api->getPastDuties($faculty_id);
                    break;
                case 'duty':
                    $duty_id = isset($_GET['duty_id']) ? (int)$_GET['duty_id'] : null;
                    if (!$duty_id) {
                        throw new Exception("Duty ID is required");
                    }
                    $result = $api->getDutyById($duty_id);
                    break;
                default:
                    $result = $api->getInvigilatorDuties($faculty_id);
            }

            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;

        case 'PUT':
            if (!isset($input['duty_id']) || !isset($input['status'])) {
                throw new Exception("Duty ID and status are required");
            }

            $notes = isset($input['notes']) ? $input['notes'] : '';
            $result = $api->updateDutyStatus($input['duty_id'], $input['status'], $notes);

            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Duty status updated successfully' : 'Failed to update duty status'
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
