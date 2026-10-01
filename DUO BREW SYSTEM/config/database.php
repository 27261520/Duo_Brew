<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Database Connection Class
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            // Enable error throwing for mysqli operations
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $this->connection->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    /**
     * Execute a prepared statement query
     */
    public function query($sql, $params = [], $types = '') {
        try {
            $stmt = $this->connection->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }
            
            if (!empty($params)) {
                if (empty($types)) {
                    $types = str_repeat('s', count($params));
                }
                
                // Bind parameters with reference compatibility
                $bindParams = [$types];
                foreach ($params as &$param) {
                    $bindParams[] = &$param;
                }
                unset($param);
                call_user_func_array([$stmt, 'bind_param'], $bindParams);
            }
            
            $stmt->execute();
            return $stmt;
        } catch (mysqli_sql_exception $e) {
            throw new Exception("Database Exception: " . $e->getMessage() . " in query: " . $sql);
        } catch (Exception $e) {
            throw new Exception("Query Error: " . $e->getMessage());
        }
    }

    /**
     * Get multiple rows as associative array
     */
    public function getRows($sql, $params = [], $types = '') {
        $stmt = $this->query($sql, $params, $types);
        $result = $stmt->get_result();
        $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $data;
    }

    /**
     * Get single row as associative array
     */
    public function getRow($sql, $params = [], $types = '') {
        $stmt = $this->query($sql, $params, $types);
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $row;
    }

    /**
     * Get single value
     */
    public function getValue($sql, $params = [], $types = '') {
        $stmt = $this->query($sql, $params, $types);
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_array() : null;
        $stmt->close();
        return $row ? $row[0] : null;
    }

    /**
     * Insert row and return insert ID
     */
    public function insert($sql, $params = [], $types = '') {
        $stmt = $this->query($sql, $params, $types);
        $insertId = $stmt->insert_id;
        $stmt->close();
        return $insertId;
    }

    /**
     * Update/Delete and return affected rows
     */
    public function execute($sql, $params = [], $types = '') {
        $stmt = $this->query($sql, $params, $types);
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        return $affectedRows;
    }

    /**
     * Escape string
     */
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }

    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->connection->insert_id;
    }

    /**
     * Close connection
     */
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}

// Helper function for easy database access
function db() {
    return Database::getInstance();
}