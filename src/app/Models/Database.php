<?php


class Database
{
    private static $instance = null;
    private $connection = null;

    private function __construct()
    {
        // Проверяем, определена ли константа
        if (!defined('BASE_PATH')) {
            throw new \RuntimeException('BASE_PATH constant is not defined');
        }

        $configPath = BASE_PATH . '/config/database.php';
        if (!file_exists($configPath)) {
            throw new \RuntimeException('Database configuration file not found');
        }

        $config = require $configPath;

        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->connection = new \PDO($dsn, $config['username'], $config['password'], $options);
        } catch (\PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new \RuntimeException('Database connection failed');
        }
    }

    // Запрет клонирования
    private function __clone()
    {
    }

    /**
     * Получение экземпляра класса Database (Singleton)
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Получение соединения с базой данных
     */
    public function getConnection()
    {
        return $this->connection;
    }
}