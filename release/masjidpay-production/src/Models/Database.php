<?php
namespace Models;

class Database
{
    private static $instance = null;
    private \PDO $conn;

    private function __construct()
    {
        $host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $name = defined('DB_NAME') ? DB_NAME : 'masjidpay';
        $user = defined('DB_USER') ? DB_USER : 'root';
        $pass = defined('DB_PASS') ? DB_PASS : '';

        try {
            $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
            $this->conn = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (\PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }
    }

    public static function connect(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConn(): \PDO
    {
        return $this->conn;
    }

    /**
     * Check if the connection is still alive and reconnect if necessary.
     * This prevents "MySQL server has gone away" errors on long-idle connections.
     */
    private function checkConnection(): void
    {
        try {
            $stmt = $this->conn->query('SELECT 1');
            $stmt->closeCursor();
        } catch (\PDOException $e) {
            // Connection lost — reconnect
            $host = defined('DB_HOST') ? DB_HOST : 'localhost';
            $name = defined('DB_NAME') ? DB_NAME : 'masjidpay';
            $user = defined('DB_USER') ? DB_USER : 'root';
            $pass = defined('DB_PASS') ? DB_PASS : '';

            $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
            $this->conn = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $this->checkConnection();
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $this->checkConnection();
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function execute(string $sql, array $params = []): int
    {
        $this->checkConnection();
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function insert(string $sql, array $params = []): string
    {
        $this->checkConnection();
        $this->execute($sql, $params);
        return $this->conn->lastInsertId();
    }
}