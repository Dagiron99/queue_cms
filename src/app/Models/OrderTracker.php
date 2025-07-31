<?php

namespace App\Models;

use App\Services\DatabaseService;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
// use App\Database\Database; // Commented out as the class is not found



use PDO;
use PDOException;



/**
 * Модель для работы с заказами и их отслеживанием
 * 
 * @version 2.0
 */
class OrderTracker
{
    private $db;
    private $logger;
    private $tableFields; // Кэш полей таблицы
    private $tableName = 'orders_tracking'; // Унифицированное имя таблицы

    /**
     * Инициализация модели
     */
    public function __construct()
    {
        $this->logger = new Logger('order_tracker');

        $this->db = DatabaseService::getInstance()->getConnection(); // Updated to use DatabaseService
        $this->tableFields = $this->getTableFields();
    }

    /**
     * Получение списка полей таблицы orders_tracking
     */
    private function getTableFields()
    {
        try {
            $stmt = $this->db->prepare("DESCRIBE {$this->tableName}");
            $stmt->execute();
            $fields = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $fields;
        } catch (PDOException $e) {
            $this->logger->error("Error getting table fields: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Проверка существования поля в таблице
     */
    private function fieldExists($fieldName)
    {
        return in_array($fieldName, $this->tableFields);
    }

    /**
     * Добавление заказа для отслеживания с учетом существующих полей
     */
    public function addOrder($orderData)
    {
        try {
            // Создаем SQL-запрос динамически на основе доступных полей
            $fields = [];
            $placeholders = [];
            $bindings = [];

            // Базовые поля, которые должны существовать
            $requiredFields = [
                'order_id' => PDO::PARAM_STR,
                'manager_id' => PDO::PARAM_INT,
                'current_status' => PDO::PARAM_STR,
                'initial_status' => PDO::PARAM_STR,
                'assigned_at' => PDO::PARAM_STR,
                'last_checked_at' => PDO::PARAM_STR,
                'processed' => PDO::PARAM_INT
            ];

            // Опциональные поля, которые могут существовать
            $optionalFields = [
                'platform' => PDO::PARAM_STR,
                'queue_id' => PDO::PARAM_INT,
                'data' => PDO::PARAM_STR,
                'created_at' => PDO::PARAM_STR,
                'updated_at' => PDO::PARAM_STR,
                'distribution_algorithm' => PDO::PARAM_STR
            ];

            // Добавляем базовые поля
            foreach ($requiredFields as $field => $type) {
                // Адаптируем старое имя поля к новому
                $value = null;
                if ($field == 'current_status' && isset($orderData['status'])) {
                    $value = $orderData['status'];
                } elseif ($field == 'initial_status' && isset($orderData['status'])) {
                    $value = $orderData['status'];
                } elseif (isset($orderData[$field])) {
                    $value = isset($orderData[$field]) ? $orderData[$field] : null;
                }

                // Если поле отсутствует, устанавливаем значение по умолчанию
                if ($value === null) {
                    if ($field == 'current_status' || $field == 'initial_status') {
                        $value = 'new';
                    } elseif ($field == 'assigned_at' || $field == 'last_checked_at') {
                        $value = date('Y-m-d H:i:s');
                    } elseif ($field == 'processed') {
                        $value = 0;
                    }
                }

                if ($this->fieldExists($field)) {
                    $fields[] = $field;
                    $placeholders[] = ":$field";
                    $bindings[$field] = ['value' => $value, 'type' => $type];
                }
            }

            // Добавляем опциональные поля, если они существуют в таблице
            foreach ($optionalFields as $field => $type) {
                if ($this->fieldExists($field) && isset($orderData[$field])) {
                    $value = $criteria[$field];
                    
                    // Обработка специальных полей
                    if ($field == 'data' && is_array($value)) {
                        $value = json_encode($value);
                    }
                    
                    $fields[] = $field;
                    $placeholders[] = ":$field";
                    $bindings[$field] = ['value' => $value, 'type' => $type];
                }
            }

            // Добавляем NOW() для полей дат, если они не установлены
            $dateFields = ['assigned_at', 'last_checked_at', 'created_at'];
            foreach ($dateFields as $field) {
                if ($this->fieldExists($field) && !isset($bindings[$field]) && !in_array($field, $fields)) {
                    $fields[] = $field;
                    $placeholders[] = "NOW()";
                }
            }

            // Формируем SQL-запрос
            $sql = "INSERT INTO {$this->tableName} (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";
            $stmt = $this->db->prepare($sql);

            // Привязываем параметры
            foreach ($bindings as $param => $data) {
                $stmt->bindValue(":$param", $data['value'], $data['type']);
            }

            $result = $stmt->execute();

            if ($result) {
                $this->logger->info("Order {$orderData['order_id']} added for tracking");
                
                // Добавляем запись в историю статусов, если таблица существует
                $this->addStatusHistory($orderData['order_id'], $orderData['status'] ?? 'new');
            } else {
                $this->logger->error("Failed to add order {$orderData['order_id']} for tracking");
            }

            return $result;
        } catch (PDOException $e) {
            $this->logger->error("Error adding order for tracking: " . $e->getMessage());
            return false;
        }
    }



    /**
     * Получение заказов с учетом существующих полей
     */

    public function getOrders($criteria = [])
    {
        try {
            // Создаем SQL-запрос динамически на основе доступных полей
            $fields = [];
            $bindings = [];

            // Базовые поля, которые должны существовать
            $requiredFields = [
                'current_status' => PDO::PARAM_STR,
                'assigned_at' => PDO::PARAM_STR,
                'last_checked_at' => PDO::PARAM_STR,
                'processed' => PDO::PARAM_INT
            ];

            // Опциональные поля, которые могут существовать
            $optionalFields = [
                'platform' => PDO::PARAM_STR,
                'queue_id' => PDO::PARAM_INT,
                'data' => PDO::PARAM_STR,
                'updated_at' => PDO::PARAM_STR,
                'distribution_algorithm' => PDO::PARAM_STR
            ];

            // Добавляем базовые поля
            foreach ($requiredFields as $field => $type) {
                if (isset($orderData[$field]) && $this->fieldExists($field)) {
                    $fields[] = "$field = :$field";
                    $bindings[$field] = ['value' => $criteria[$field], 'type' => $type];
                }
            }

            // Добавляем опциональные поля, если они существуют в таблице
            foreach ($optionalFields as $field => $type) {
                if ($this->fieldExists($field) && isset($orderData[$field])) {
                    $value = $orderData[$field];
                    
                    // Обработка специальных полей
                    if ($field == 'data' && is_array($value)) {
                        $value = json_encode($value);
                    }
                    
                    $fields[] = "$field = :$field";
                    $bindings[$field] = ['value' => $value, 'type' => $type];
                }
            }

            // Добавляем NOW() для полей дат, если они не установлены
            if ($this->fieldExists('updated_at') && !isset($bindings['updated_at'])) {
                $fields[] = "updated_at = NOW()";
            }

            // Формируем SQL-запрос
            if (empty($fields)) {
                throw new \Exception("No fields to update");
            }

            $sql = "UPDATE {$this->tableName} SET " . implode(", ", $fields) . " WHERE order_id = :order_id";
            $sql = "SELECT * FROM {$this->tableName}";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error("Error getting orders: " . $e->getMessage());
            return [];
        }
    }
    /**
     * Обновление статуса заказа
     */
    public function updateOrderStatus($orderId, $newStatus, $comment = null)
    {
        try {
            // Определяем нужное имя поля статуса
            $statusField = $this->fieldExists('current_status') ? 'current_status' : 'status';
            
            // Создаем запрос с учетом существующих полей
            $updateFields = [
                "$statusField = :status",
                "last_checked_at = NOW()",
                "processed = 1"
            ];
            
            // Добавляем processed_at, если поле существует
            if ($this->fieldExists('processed_at')) {
                $updateFields[] = "processed_at = NOW()";
            }
            
            // Добавляем updated_at, если поле существует
            if ($this->fieldExists('updated_at')) {
                $updateFields[] = "updated_at = NOW()";
            }
            
            $sql = "UPDATE {$this->tableName} SET " . implode(", ", $updateFields) . " WHERE order_id = :order_id";
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':status', $newStatus, PDO::PARAM_STR);
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_STR);
            
            $result = $stmt->execute();
            
            if ($result) {
                $this->logger->info("Status for order {$orderId} updated to {$newStatus}");
                
                // Добавляем запись в историю статусов, если есть комментарий
                if ($comment) {
                    $this->addStatusHistory($orderId, $newStatus, ['comment' => $comment]);
                } else {
                    $this->addStatusHistory($orderId, $newStatus);
                }
            } else {
                $this->logger->error("Failed to update status for order {$orderId}");
            }
            
            return $result;
        } catch (PDOException $e) {
            $this->logger->error("Error updating order status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Добавление записи в историю статусов (с проверкой существования таблицы)
     */
    private function addStatusHistory($orderId, $status, $platformData = null)
    {
        try {
            // Проверяем существование таблицы
            $stmt = $this->db->prepare("
                SELECT 1 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = 'order_status_history'
                LIMIT 1
            ");
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                // Таблица не существует, создаем ее
                $this->createStatusHistoryTable();
            }
            
            // Добавляем запись
            $stmt = $this->db->prepare("
                INSERT INTO order_status_history (
                    order_id,
                    status,
                    platform_data,
                    created_at
                ) VALUES (
                    :order_id,
                    :status,
                    :platform_data,
                    NOW()
                )
            ");
            
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_STR);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            
            $jsonData = $platformData ? json_encode($platformData) : null;
            $stmt->bindParam(':platform_data', $jsonData, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->logger->error("Error adding status history: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Создание таблицы для истории статусов
     */
    private function createStatusHistoryTable()
    {
        try {
            $sql = "
                CREATE TABLE order_status_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id VARCHAR(255) NOT NULL,
                    status VARCHAR(50) NOT NULL,
                    platform_data TEXT DEFAULT NULL,
                    created_at DATETIME NOT NULL,
                    INDEX (order_id),
                    INDEX (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            
            $this->db->exec($sql);
            $this->logger->info("Created order_status_history table");
            return true;
        } catch (PDOException $e) {
            $this->logger->error("Error creating status history table: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получение всех отслеживаемых заказов
     */
    public function getAllOrders($limit = 100, $offset = 0)
    {
        try {
            // Определяем нужное имя поля для сортировки
            $dateField = $this->fieldExists('assigned_at') ? 'assigned_at' : 
                        ($this->fieldExists('created_at') ? 'created_at' : 'last_checked_at');
            
            $sql = "
                SELECT 
                    ot.*,
                    m.name as manager_name
                FROM {$this->tableName} ot
                LEFT JOIN managers m ON ot.manager_id = m.id
                ORDER BY ot.$dateField DESC
                LIMIT :limit OFFSET :offset
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->logger->info("getAllOrders returned " . count($result) . " rows");
            
            return $result;
        } catch (PDOException $e) {
            $this->logger->error("Error in getAllOrders: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Получение заказа по ID
     */
    public function getOrderById($orderId)
    {
        try {
            // Создаем базовый запрос
            $sql = "
                SELECT 
                    ot.*,
                    m.name as manager_name
                FROM {$this->tableName} ot
                LEFT JOIN managers m ON ot.manager_id = m.id
            ";
            
            // Добавляем JOIN с таблицей очередей, если поле queue_id существует
            if ($this->fieldExists('queue_id')) {
                $sql = "
                    SELECT 
                        ot.*,
                        m.name as manager_name,
                        q.name as queue_name
                    FROM {$this->tableName} ot
                    LEFT JOIN managers m ON ot.manager_id = m.id
                    LEFT JOIN queues q ON ot.queue_id = q.id
                ";
            }
            
            $sql .= " WHERE ot.order_id = :order_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error("Error getting order by ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получение истории статусов заказа
     */
    public function getOrderStatusHistory($orderId)
    {
        try {
            // Проверяем существование таблицы
            $stmt = $this->db->prepare("
                SELECT 1 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = 'order_status_history'
                LIMIT 1
            ");
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                // Таблица не существует, возвращаем пустой массив
                return [];
            }
            
            // Получаем историю
            $stmt = $this->db->prepare("
                SELECT *
                FROM order_status_history
                WHERE order_id = :order_id
                ORDER BY created_at DESC
            ");
            
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error("Error getting order status history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Получение статистики по заказам
     */
    public function getStatistics() 
    {
        try {
            // Определяем имя поля статуса
            $statusField = $this->fieldExists('current_status') ? 'current_status' : 'status';
            
            // Получаем общую статистику с правильными названиями полей
            $sql = "
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN $statusField IN ('new', 'processing') THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN $statusField IN ('completed', 'done', 'success') THEN 1 ELSE 0 END) as processed,
                    SUM(CASE WHEN $statusField IN ('cancelled', 'error', 'failed') THEN 1 ELSE 0 END) as failed
                FROM {$this->tableName}
            ";
            
            $stmt = $this->db->query($sql);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Получаем статистику по менеджерам
            $sql = "
                SELECT 
                    m.id,
                    m.name,
                    COUNT(ot.id) as total_orders,
                    SUM(CASE WHEN ot.$statusField IN ('completed', 'done', 'success') THEN 1 ELSE 0 END) as processed_orders,
                    SUM(CASE WHEN ot.$statusField IN ('cancelled', 'error', 'failed') THEN 1 ELSE 0 END) as failed_orders
                FROM managers m
                LEFT JOIN {$this->tableName} ot ON m.id = ot.manager_id
                WHERE m.is_active = 1
                GROUP BY m.id, m.name
                ORDER BY processed_orders DESC
            ";
            
            $stmt = $this->db->query($sql);
            $stats['managers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Получаем количество заказов за сегодня
            $dateField = $this->fieldExists('assigned_at') ? 'assigned_at' : 
                        ($this->fieldExists('created_at') ? 'created_at' : 'last_checked_at');
            
            $sql = "
                SELECT COUNT(*) as count 
                FROM {$this->tableName} 
                WHERE DATE($dateField) = CURDATE()
            ";
            
            $stmt = $this->db->query($sql);
            $stats['today'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Получаем статистику по очередям, если поле существует
            $stats['queues'] = [];
            if ($this->fieldExists('queue_id')) {
                $sql = "
                    SELECT 
                        q.id,
                        q.name,
                        COUNT(ot.id) as total_orders,
                        SUM(CASE WHEN ot.$statusField IN ('completed', 'done', 'success') THEN 1 ELSE 0 END) as processed_orders,
                        SUM(CASE WHEN ot.$statusField IN ('cancelled', 'error', 'failed') THEN 1 ELSE 0 END) as failed_orders
                    FROM queues q
                    LEFT JOIN {$this->tableName} ot ON q.id = ot.queue_id
                    WHERE q.is_active = 1
                    GROUP BY q.id, q.name
                    ORDER BY processed_orders DESC
                ";
                
                $stmt = $this->db->query($sql);
                $stats['queues'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            return $stats;
        } catch (PDOException $e) {
            $this->logger->error("Error getting order statistics: " . $e->getMessage());
            return [
                'total_orders' => 0,
                'in_progress' => 0,
                'processed' => 0,
                'failed' => 0,
                'today' => 0,
                'managers' => [],
                'queues' => []
            ];
        }
    }

    /**
     * Получение отфильтрованных заказов
     */
    public function getFilteredOrders($filters = [], $limit = 100, $offset = 0)
    {
        try {
            $conditions = [];
            $params = [];
            
            // Определяем правильные имена полей
            $statusField = $this->fieldExists('current_status') ? 'current_status' : 'status';
            $dateField = $this->fieldExists('assigned_at') ? 'assigned_at' : 
                        ($this->fieldExists('created_at') ? 'created_at' : 'last_checked_at');
            
            // Подготовка условий фильтрации с правильными именами полей
            if (!empty($filters['status'])) {
                $conditions[] = "ot.$statusField = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['manager_id'])) {
                $conditions[] = "ot.manager_id = :manager_id";
                $params[':manager_id'] = $filters['manager_id'];
            }
            
            // Добавляем фильтр по queue_id, если поле существует
            if (!empty($filters['queue_id']) && $this->fieldExists('queue_id')) {
                $conditions[] = "ot.queue_id = :queue_id";
                $params[':queue_id'] = $filters['queue_id'];
            }
            
            if (!empty($filters['date_from'])) {
                $conditions[] = "ot.$dateField >= :date_from";
                $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
            }
            
            if (!empty($filters['date_to'])) {
                $conditions[] = "ot.$dateField <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }
            
            // Добавляем условие по order_id, если указано
            if (!empty($filters['order_id'])) {
                $conditions[] = "ot.order_id LIKE :order_id";
                $params[':order_id'] = '%' . $filters['order_id'] . '%';
            }
            
            $whereClause = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";
            
            // Базовый запрос
            $sql = "
                SELECT 
                    ot.*,
                    m.name as manager_name
            ";
            
            // Добавляем поле queue_name, если queue_id существует
            if ($this->fieldExists('queue_id')) {
                $sql .= ", q.name as queue_name";
            }
            
            $sql .= "
                FROM {$this->tableName} ot
                LEFT JOIN managers m ON ot.manager_id = m.id
            ";
            
            // Добавляем JOIN с таблицей очередей, если поле существует
            if ($this->fieldExists('queue_id')) {
                $sql .= "LEFT JOIN queues q ON ot.queue_id = q.id";
            }
            
            $sql .= $whereClause . " ORDER BY ot.$dateField DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            
            // Привязываем параметры
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error("Error getting filtered orders: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Получение количества активных заказов у менеджера
     */
    public function getActiveOrdersCount($managerId)
    {
        try {
            // Определяем имя поля статуса
            $statusField = $this->fieldExists('current_status') ? 'current_status' : 'status';
            
            $sql = "
                SELECT COUNT(*) as count
                FROM {$this->tableName}
                WHERE manager_id = :manager_id
                AND $statusField IN ('new', 'processing', 'assigned', 'in_progress')
            ";
            
            // Добавляем условие processed, если поле существует
            if ($this->fieldExists('processed')) {
                $sql .= " AND processed = 0";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':manager_id', $managerId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (PDOException $e) {
            $this->logger->error("Error getting manager active orders count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Получение количества заказов по дате
     */
    public function getOrdersCountByDate($date)
    {
        try {
            // Определяем поле даты
            $dateField = $this->fieldExists('assigned_at') ? 'assigned_at' : 
                        ($this->fieldExists('created_at') ? 'created_at' : 'last_checked_at');
            
            $sql = "
                SELECT COUNT(*) as count
                FROM {$this->tableName}
                WHERE DATE($dateField) = :date
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':date', $date, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (PDOException $e) {
            $this->logger->error("Error getting orders count by date: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Получение статистики заказов по статусам
     */
    public function getOrdersCountByStatus()
    {
        try {
            // Определяем имя поля статуса
            $statusField = $this->fieldExists('current_status') ? 'current_status' : 'status';
            
            $sql = "
                SELECT 
                    $statusField as status,
                    COUNT(*) as count
                FROM {$this->tableName}
                GROUP BY $statusField
            ";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error("Error getting orders count by status: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Получение последних заказов
     */
    public function getRecentOrders($limit = 5)
    {
        try {
            // Определяем поле даты для сортировки
            $dateField = $this->fieldExists('assigned_at') ? 'assigned_at' : 
                        ($this->fieldExists('created_at') ? 'created_at' : 'last_checked_at');
            
            // Базовый запрос
            $sql = "
                SELECT 
                    ot.*,
                    m.name as manager_name
            ";
            
            // Добавляем поле queue_name, если queue_id существует
            if ($this->fieldExists('queue_id')) {
                $sql .= ", q.name as queue_name";
            }
            
            $sql .= "
                FROM {$this->tableName} ot
                LEFT JOIN managers m ON ot.manager_id = m.id
            ";
            
            // Добавляем JOIN с таблицей очередей, если поле существует
            if ($this->fieldExists('queue_id')) {
                $sql .= "LEFT JOIN queues q ON ot.queue_id = q.id";
            }
            
            $sql .= " ORDER BY ot.$dateField DESC LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error("Error getting recent orders: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Получение статистики по алгоритмам распределения
     */
    public function getDistributionAlgorithmStats($queueId = null, $dateFrom = null, $dateTo = null)
    {
        try {
            // Проверяем, существует ли поле distribution_algorithm
            if (!$this->fieldExists('distribution_algorithm')) {
                return [
                    'round_robin' => 0,
                    'load_balanced' => 0,
                    'priority_based' => 0
                ];
            }

            $conditions = [];
            $params = [];
            
            if ($queueId) {
                $conditions[] = "queue_id = :queue_id";
                $params[':queue_id'] = $queueId;
            }
            
            if ($dateFrom) {
                $conditions[] = "DATE(created_at) >= :date_from";
                $params[':date_from'] = $dateFrom;
            }
            
            if ($dateTo) {
                $conditions[] = "DATE(created_at) <= :date_to";
                $params[':date_to'] = $dateTo;
            }
            
            $whereClause = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";
            
            $sql = "
                SELECT 
                    distribution_algorithm,
                    COUNT(*) as count
                FROM {$this->tableName}
                $whereClause
                GROUP BY distribution_algorithm
            ";
            
            $stmt = $this->db->prepare($sql);
            
            // Привязываем параметры
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Преобразуем результат в удобный формат
            $stats = [
                'round_robin' => 0,
                'load_balanced' => 0,
                'priority_based' => 0
            ];
            
            foreach ($rows as $row) {
                $algorithm = $row['distribution_algorithm'];
                if (isset($stats[$algorithm])) {
                    $stats[$algorithm] = (int) $row['count'];
                }
            }
            
            return $stats;
        } catch (PDOException $e) {
            $this->logger->error("Error getting distribution algorithm stats: " . $e->getMessage());
            return [
                'round_robin' => 0,
                'load_balanced' => 0,
                'priority_based' => 0
            ];
        }
    }
    
    /**
     * Получение статистики распределения по менеджерам
     */
    public function getManagersDistributionStats($queueId = null, $dateFrom = null, $dateTo = null)
    {
        try {
            // Определяем имя поля статуса
            $statusField = $this->fieldExists('current_status') ? 'current_status' : 'status';
            
            $conditions = [];
            $params = [];
            
            if ($queueId) {
                $conditions[] = "ot.queue_id = :queue_id";
                $params[':queue_id'] = $queueId;
            }
            
            if ($dateFrom) {
                $conditions[] = "DATE(ot.created_at) >= :date_from";
                $params[':date_from'] = $dateFrom;
            }
            
            if ($dateTo) {
                $conditions[] = "DATE(ot.created_at) <= :date_to";
                $params[':date_to'] = $dateTo;
            }
            
            $whereClause = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";
            
            $sql = "
                SELECT 
                    m.id,
                    m.name,
                    COUNT(ot.id) as total_orders,
                    SUM(CASE WHEN ot.$statusField = 'assigned' OR ot.$statusField = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN ot.$statusField = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN ot.$statusField = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                FROM managers m
                LEFT JOIN {$this->tableName} ot ON m.id = ot.manager_id
                $whereClause
                GROUP BY m.id, m.name
                ORDER BY total_orders DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            
            // Привязываем параметры
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error("Error getting managers distribution stats: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получение всех отслеживаемых ID заказов
     */
    public function getAllTrackedOrderIds()
    {
        try {
            $sql = "SELECT order_id FROM {$this->tableName}";
            $stmt = $this->db->query($sql);
            
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'order_id');
        } catch (PDOException $e) {
            $this->logger->error("Error getting all tracked order IDs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Отслеживание заказа (добавление или обновление)
     */
    public function trackOrder($orderId, $managerId, $queueId, $orderData, $status = 'assigned')
    {
        try {
            // Проверяем, существует ли уже заказ
            $existingOrder = $this->getOrderById($orderId);
            
            if ($existingOrder) {
                // Обновляем существующий заказ
                return $this->updateOrderStatus($orderId, $status);
            } else {
                // Добавляем новый заказ
                $data = [
                    'order_id' => $orderId,
                    'manager_id' => $managerId,
                    'queue_id' => $queueId,
                    'data' => $orderData,
                    'status' => $status,
                    'assigned_at' => date('Y-m-d H:i:s')
                ];
                
                return $this->addOrder($data);
            }
        } catch (PDOException $e) {
            $this->logger->error("Error tracking order: " . $e->getMessage());
            return false;
        }
    }
    // Removed duplicate method declaration to resolve the error.

     
}