<?php
/**
 * LightBlog CMS - Database Wrapper
 * Lightweight PDO wrapper for SQLite and MySQL
 */

class Database {
    private $pdo;
    private static $instance = null;

    /**
     * Constructor - Initialize database connection
     */
    public function __construct() {
        try {
            $dbType = defined('DB_TYPE') ? DB_TYPE : 'sqlite';

            if ($dbType === 'sqlite') {
                $dbPath = defined('CONTENT_PATH') ? CONTENT_PATH . '/database/lightblog.db' : 'content/database/lightblog.db';
                $dsn = 'sqlite:' . $dbPath;
                $this->pdo = new PDO($dsn);
            } else {
                // MySQL
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]);
            }

            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Get singleton instance
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Execute a query and return results
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return array Results
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, $params);
            throw $e;
        }
    }

    /**
     * Execute a query and return single row
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return object|null Single result
     */
    public function queryOne($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, $params);
            throw $e;
        }
    }

    /**
     * Insert a record
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return int Last insert ID
     */
    public function insert($table, $data) {
        try {
            $keys = array_keys($data);
            // Escape column names with backticks for MySQL reserved keywords
            $columns = implode(', ', array_map(function($k) { return "`$k`"; }, $keys));
            $placeholders = ':' . implode(', :', $keys);

            $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);

            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql ?? '', $data);
            throw $e;
        }
    }

    /**
     * Update records
     * @param string $table Table name
     * @param array $data Data to update
     * @param string $where WHERE clause
     * @param array $whereParams WHERE parameters
     * @return int Number of affected rows
     */
    public function update($table, $data, $where, $whereParams = []) {
        try {
            $set = [];
            foreach (array_keys($data) as $key) {
                // Escape column names with backticks for MySQL reserved keywords
                $set[] = "`$key` = :$key";
            }
            $setClause = implode(', ', $set);

            $sql = "UPDATE $table SET $setClause WHERE $where";
            $stmt = $this->pdo->prepare($sql);

            // Merge data and where params
            $params = array_merge($data, $whereParams);
            $stmt->execute($params);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql ?? '', $params ?? []);
            throw $e;
        }
    }

    /**
     * Delete records
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params WHERE parameters
     * @return int Number of deleted rows
     */
    public function delete($table, $where, $params = []) {
        try {
            $sql = "DELETE FROM $table WHERE $where";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, $params);
            throw $e;
        }
    }

    /**
     * Execute a raw SQL statement
     * @param string $sql SQL statement
     * @return bool Success
     */
    public function exec($sql) {
        try {
            return $this->pdo->exec($sql);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, []);
            throw $e;
        }
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->pdo->rollBack();
    }

    /**
     * Get PDO instance
     * @return PDO
     */
    public function getPdo() {
        return $this->pdo;
    }

    /**
     * Log database errors
     * @param string $message Error message
     * @param string $sql SQL query
     * @param array $params Parameters
     */
    private function logError($message, $sql, $params) {
        $logFile = defined('CONTENT_PATH') ? CONTENT_PATH . '/database/error.log' : 'content/database/error.log';

        $logMessage = date('Y-m-d H:i:s') . " - " . $message . "\n";
        $logMessage .= "SQL: " . $sql . "\n";
        $logMessage .= "Params: " . json_encode($params) . "\n\n";

        error_log($logMessage, 3, $logFile);
    }

    /**
     * Count records
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params Parameters
     * @return int Count
     */
    public function count($table, $where = '1=1', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM $table WHERE $where";
        $result = $this->queryOne($sql, $params);
        return $result ? (int)$result->count : 0;
    }

    /**
     * Check if record exists
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params Parameters
     * @return bool
     */
    public function exists($table, $where, $params = []) {
        return $this->count($table, $where, $params) > 0;
    }
}
