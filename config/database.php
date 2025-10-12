<?php
/**
 * Database Configuration for Exam Management System
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'reg';
    private $username = 'root';
    private $password = '';
    private $conn;

    /**
     * Get database connection
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                )
            );
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }

    /**
     * Close database connection
     */
    public function closeConnection() {
        $this->conn = null;
    }
}

/**
 * Utility functions for common database operations
 */
class DatabaseUtils {
    
    /**
     * Execute a prepared statement with parameters
     */
    public static function executeQuery($conn, $query, $params = []) {
        try {
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    /**
     * Fetch all results from a query
     */
    public static function fetchAll($conn, $query, $params = []) {
        $stmt = self::executeQuery($conn, $query, $params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch single result from a query
     */
    public static function fetchOne($conn, $query, $params = []) {
        $stmt = self::executeQuery($conn, $query, $params);
        return $stmt->fetch();
    }

    /**
     * Get the last inserted ID
     */
    public static function getLastInsertId($conn) {
        return $conn->lastInsertId();
    }
}
?>
