<?php
/**
 * Database Connection Class
 * 
 * PDO wrapper class for secure database operations
 * Handles connection, queries, CRUD operations, and error logging
 * 
 * @package LankanLens
 * @version 1.0
 */

class Database {
    private $pdo;
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;
    private $error_log_path;

    /**
     * Constructor - Load environment variables and establish PDO connection
     * 
     * @throws PDOException if connection fails
     */
    public function __construct() {
        // Load configuration constants
        require_once __DIR__ . '/config.php';

        // Set database connection parameters
        $this->host = DB_HOST;
        $this->dbname = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;
        $this->error_log_path = LOGS_PATH . '/errors.log';

        // Establish PDO connection
        $this->connect();
    }

    /**
     * Establish PDO connection with error handling
     * 
     * @return void
     */
    private function connect() {
        try {
            // Build DSN (Data Source Name)
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";

            // PDO connection options
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Throw exceptions on errors
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Fetch associative arrays by default
                PDO::ATTR_EMULATE_PREPARES   => false,                   // Use real prepared statements
                PDO::ATTR_PERSISTENT         => false,                   // Don't use persistent connections
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"  // Set charset on connection
            ];

            // Create PDO instance
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);

        } catch (PDOException $e) {
            // Log error to file
            $this->logError('Database Connection Error: ' . $e->getMessage());
            
            // Re-throw exception for caller to handle
            throw new PDOException('Database connection failed. Please check your configuration.');
        }
    }

    /**
     * Execute a query with prepared statements
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind (optional)
     * @return PDOStatement
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logError('Query Error: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw $e;
        }
    }

    /**
     * Fetch a single row from query
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind (optional)
     * @return array|false Associative array or false if no result
     */
    public function fetchOne($sql, $params = []) {
        try {
            $stmt = $this->query($sql, $params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logError('FetchOne Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch all rows from query
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind (optional)
     * @return array Array of associative arrays
     */
    public function fetchAll($sql, $params = []) {
        try {
            $stmt = $this->query($sql, $params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logError('FetchAll Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Insert a new record into a table
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value pairs
     * @return int|false Last insert ID or false on failure
     */
    public function insert($table, $data) {
        try {
            // Build column list and placeholders
            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));

            // Build SQL
            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

            // Execute query
            $stmt = $this->query($sql, $data);

            // Return last insert ID
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            $this->logError('Insert Error: ' . $e->getMessage() . ' | Table: ' . $table);
            return false;
        }
    }

    /**
     * Update records in a table
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value pairs to update
     * @param array $where Associative array of WHERE conditions (column => value)
     * @return int|false Number of affected rows or false on failure
     */
    public function update($table, $data, $where) {
        try {
            // Build SET clause
            $set = [];
            foreach ($data as $column => $value) {
                $set[] = "{$column} = :{$column}";
            }
            $setClause = implode(', ', $set);

            // Build WHERE clause
            $whereConditions = [];
            $whereParams = [];
            foreach ($where as $column => $value) {
                $whereKey = "where_{$column}";
                $whereConditions[] = "{$column} = :{$whereKey}";
                $whereParams[$whereKey] = $value;
            }
            $whereClause = implode(' AND ', $whereConditions);

            // Build SQL
            $sql = "UPDATE {$table} SET {$setClause} WHERE {$whereClause}";

            // Merge parameters
            $params = array_merge($data, $whereParams);

            // Execute query
            $stmt = $this->query($sql, $params);

            // Return affected rows
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError('Update Error: ' . $e->getMessage() . ' | Table: ' . $table);
            return false;
        }
    }

    /**
     * Delete records from a table
     * 
     * @param string $table Table name
     * @param array $where Associative array of WHERE conditions (column => value)
     * @return int|false Number of affected rows or false on failure
     */
    public function delete($table, $where) {
        try {
            // Build WHERE clause
            $whereConditions = [];
            foreach ($where as $column => $value) {
                $whereConditions[] = "{$column} = :{$column}";
            }
            $whereClause = implode(' AND ', $whereConditions);

            // Build SQL
            $sql = "DELETE FROM {$table} WHERE {$whereClause}";

            // Execute query
            $stmt = $this->query($sql, $where);

            // Return affected rows
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError('Delete Error: ' . $e->getMessage() . ' | Table: ' . $table);
            return false;
        }
    }

    /**
     * Begin a database transaction
     * 
     * @return bool
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit a database transaction
     * 
     * @return bool
     */
    public function commit() {
        return $this->pdo->commit();
    }

    /**
     * Rollback a database transaction
     * 
     * @return bool
     */
    public function rollback() {
        return $this->pdo->rollBack();
    }

    /**
     * Get the PDO instance directly for advanced operations
     * 
     * @return PDO
     */
    public function getPDO() {
        return $this->pdo;
    }

    /**
     * Check if connection is active
     * 
     * @return bool
     */
    public function isConnected() {
        return $this->pdo !== null;
    }

    /**
     * Log error to /logs/errors.log
     * 
     * @param string $message Error message to log
     * @return void
     */
    private function logError($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        
        // Ensure logs directory exists
        if (!is_dir(dirname($this->error_log_path))) {
            mkdir(dirname($this->error_log_path), 0755, true);
        }

        // Append to error log file
        error_log($logMessage, 3, $this->error_log_path);
    }

    /**
     * Destructor - Close connection (handled automatically by PDO)
     */
    public function __destruct() {
        $this->pdo = null;
    }
}
