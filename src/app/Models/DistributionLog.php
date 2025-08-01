<?php
require_once __DIR__ . '/Database.php';

class DistributionLog {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Добавление записи в лог
     */
    public function addLog($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO distribution_logs (queue_id, manager_id, order_id, status, description, created_at)
                VALUES (:queue_id, :manager_id, :order_id, :status, :description, NOW())
            ");
            
            $stmt->bindParam(':queue_id', $data['queue_id'], PDO::PARAM_INT);
            $stmt->bindParam(':manager_id', $data['manager_id'], $data['manager_id'] ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindParam(':order_id', $data['order_id'], PDO::PARAM_STR);
            $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
            $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error adding log: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получение логов с фильтрацией и пагинацией
     */
    public function getLogs($filter = [], $limit = null, $offset = 0) {
        try {
            $query = "
                SELECT 
                    dl.*,
                    q.name AS queue_name,
                    m.name AS manager_name
                FROM 
                    distribution_logs dl
                LEFT JOIN 
                    queues q ON dl.queue_id = q.id
                LEFT JOIN 
                    managers m ON dl.manager_id = m.id
                WHERE 1=1
            ";
            
            $params = [];
            
            // Применяем фильтры
            if (!empty($filter['queue_id'])) {
                $query .= " AND dl.queue_id = :queue_id";
                $params[':queue_id'] = $filter['queue_id'];
            }
            
            if (!empty($filter['manager_id'])) {
                $query .= " AND dl.manager_id = :manager_id";
                $params[':manager_id'] = $filter['manager_id'];
            }
            
            if (!empty($filter['status'])) {
                $query .= " AND dl.status = :status";
                $params[':status'] = $filter['status'];
            }
            
            if (!empty($filter['date_from'])) {
                $query .= " AND DATE(dl.created_at) >= :date_from";
                $params[':date_from'] = $filter['date_from'];
            }
            
            if (!empty($filter['date_to'])) {
                $query .= " AND DATE(dl.created_at) <= :date_to";
                $params[':date_to'] = $filter['date_to'];
            }
            
            if (!empty($filter['search'])) {
                $query .= " AND (dl.order_id LIKE :search OR dl.description LIKE :search)";
                $params[':search'] = '%' . $filter['search'] . '%';
            }
            
            // Сортировка
            $query .= " ORDER BY dl.created_at DESC";
            
            // Лимит и смещение для пагинации
            if ($limit !== null) {
                $query .= " LIMIT :limit OFFSET :offset";
                $params[':limit'] = $limit;
                $params[':offset'] = $offset;
            }
            
            $stmt = $this->db->prepare($query);
            
            // Привязываем параметры
            foreach ($params as $key => $value) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($key, $value, $type);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting logs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получение общего количества логов с учетом фильтрации
     */
    public function getTotalLogs($filter = []) {
        try {
            $query = "
                SELECT 
                    COUNT(*) as total
                FROM 
                    distribution_logs dl
                WHERE 1=1
            ";
            
            $params = [];
            
            // Применяем фильтры
            if (!empty($filter['queue_id'])) {
                $query .= " AND dl.queue_id = :queue_id";
                $params[':queue_id'] = $filter['queue_id'];
            }
            
            if (!empty($filter['manager_id'])) {
                $query .= " AND dl.manager_id = :manager_id";
                $params[':manager_id'] = $filter['manager_id'];
            }
            
            if (!empty($filter['status'])) {
                $query .= " AND dl.status = :status";
                $params[':status'] = $filter['status'];
            }
            
            if (!empty($filter['date_from'])) {
                $query .= " AND DATE(dl.created_at) >= :date_from";
                $params[':date_from'] = $filter['date_from'];
            }
            
            if (!empty($filter['date_to'])) {
                $query .= " AND DATE(dl.created_at) <= :date_to";
                $params[':date_to'] = $filter['date_to'];
            }
            
            if (!empty($filter['search'])) {
                $query .= " AND (dl.order_id LIKE :search OR dl.description LIKE :search)";
                $params[':search'] = '%' . $filter['search'] . '%';
            }
            
            $stmt = $this->db->prepare($query);
            
            // Привязываем параметры
            foreach ($params as $key => $value) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($key, $value, $type);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total'];
        } catch (PDOException $e) {
            error_log("Error getting total logs count: " . $e->getMessage());
            return 0;
        }
    }
}