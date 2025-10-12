<?php
/**
 * Faculty Authentication API
 * Handles faculty login and session management
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

class FacultyAuth {
    private $conn;
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Authenticate faculty member
     */
    public function authenticate($email, $password) {
        try {
            $query = "
                SELECT id, first_name, last_name, institute_email, department, designation
                FROM faculty 
                WHERE institute_email = :email
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute(['email' => $email]);
            $faculty = $stmt->fetch();

            if ($faculty) {
                // For demo purposes, we'll use simple password verification
                // In production, use password_hash() and password_verify()
                $query = "SELECT password_hash FROM faculty WHERE institute_email = :email";
                $stmt = $this->conn->prepare($query);
                $stmt->execute(['email' => $email]);
                $password_data = $stmt->fetch();

                // Simple password check (replace with proper hashing in production)
                if ($password_data && $password === 'demo123') { // Default demo password
                    return [
                        'success' => true,
                        'faculty' => $faculty
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Invalid credentials'
            ];

        } catch (Exception $e) {
            throw new Exception("Authentication error: " . $e->getMessage());
        }
    }

    /**
     * Get faculty by ID
     */
    public function getFacultyById($faculty_id) {
        try {
            $query = "
                SELECT id, first_name, last_name, institute_email, department, designation
                FROM faculty 
                WHERE id = :faculty_id
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute(['faculty_id' => $faculty_id]);
            $faculty = $stmt->fetch();

            return $faculty;

        } catch (Exception $e) {
            throw new Exception("Error fetching faculty: " . $e->getMessage());
        }
    }

    /**
     * Get all faculty members
     */
    public function getAllFaculty() {
        try {
            $query = "
                SELECT id, first_name, last_name, institute_email, department, designation
                FROM faculty 
                ORDER BY first_name, last_name
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $faculty = $stmt->fetchAll();

            return $faculty;

        } catch (Exception $e) {
            throw new Exception("Error fetching faculty list: " . $e->getMessage());
        }
    }
}

// Handle API requests
try {
    $auth = new FacultyAuth();
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($method) {
        case 'POST':
            if (!isset($input['email']) || !isset($input['password'])) {
                throw new Exception("Email and password are required");
            }

            $result = $auth->authenticate($input['email'], $input['password']);

            if ($result['success']) {
                // Start session
                session_start();
                $_SESSION['faculty_id'] = $result['faculty']['id'];
                $_SESSION['faculty_name'] = $result['faculty']['first_name'] . ' ' . $result['faculty']['last_name'];
                $_SESSION['faculty_email'] = $result['faculty']['institute_email'];
            }

            echo json_encode($result);
            break;

        case 'GET':
            $action = isset($_GET['action']) ? $_GET['action'] : '';

            switch ($action) {
                case 'profile':
                    session_start();
                    if (!isset($_SESSION['faculty_id'])) {
                        throw new Exception("Not authenticated");
                    }
                    $faculty = $auth->getFacultyById($_SESSION['faculty_id']);
                    echo json_encode([
                        'success' => true,
                        'faculty' => $faculty
                    ]);
                    break;

                case 'list':
                    $faculty = $auth->getAllFaculty();
                    echo json_encode([
                        'success' => true,
                        'data' => $faculty
                    ]);
                    break;

                default:
                    throw new Exception("Invalid action");
            }
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
