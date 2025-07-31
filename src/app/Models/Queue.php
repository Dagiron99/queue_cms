<?php

namespace App\Models;

use App\Services\DatabaseService;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


class Queue
{
    private $db;
    private $lastQuery;
    private $logger;

    public function __construct()
    {
        $this->logger = new Logger('queue');

        $this->db = DatabaseService::getInstance()->getConnection();

    }

    /**
     * Получение всех очередей
     */
    public function getAllQueues()
    {
        try {
            $stmt = $this->db->query("SELECT * FROM queues ORDER BY id ASC");

            $this->lastQuery = $stmt->queryString;
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->logger->info("getAllQueues() вернул " . count($result) . " строк");
            return $result;
        } catch (\PDOException $e) {
            $this->logger->error("Error getting all queues: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Получение всех очередей (алиас для совместимости)
     */
    public function getAll()
    {
        return $this->getAllQueues();
    }

    /**
     * Получение очереди по ID
     */
    public function getQueueById($queueId)
    {
        try {
            // Проверка ID на валидность
            $queueId = (int) $queueId;
            if ($queueId <= 0) {
                $this->logger->error("Invalid queue ID: " . $queueId);
                return false;
            }

            $stmt = $this->db->prepare("SELECT * FROM queues WHERE id = :id");
            $stmt->bindParam(':id', $queueId, \PDO::PARAM_INT);
            $stmt->execute();

            $this->lastQuery = $stmt->queryString;
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            $this->logger->info("getQueueById($queueId) результат: " . ($result ? "найдена" : "не найдена"));
            return $result;
        } catch (\PDOException $e) {
            $this->logger->error("Error getting queue by ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получение очереди по типу
     */
    public function getQueueByType($type)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM queues WHERE type = :type AND is_active = 1 LIMIT 1");
            $stmt->bindParam(':type', $type, \PDO::PARAM_STR);
            $stmt->execute();

            $this->lastQuery = $stmt->queryString;
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Error getting queue by type: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получение последнего запроса (для отладки)
     */
    public function getLastQuery()
    {
        return $this->lastQuery ?? 'Нет запросов';
    }

    /**
     * Добавление новой очереди
     */
    public function addQueue($queueData)
    {
        try {
            $this->logger->info("addQueue() получил данные: " . print_r($queueData, true));

            $stmt = $this->db->prepare("
                INSERT INTO queues (name, algorithm, is_active, max_load, type, current_position)
                VALUES (:name, :algorithm, :is_active, :max_load, :type, :current_position)
            ");

            $currentPosition = 0;
            $stmt->bindParam(':name', $queueData['name']);
            $stmt->bindParam(':algorithm', $queueData['algorithm']);
            $stmt->bindParam(':is_active', $queueData['is_active'], \PDO::PARAM_INT);
            $stmt->bindParam(':max_load', $queueData['max_load'], \PDO::PARAM_INT);
            $stmt->bindParam(':type', $queueData['type']);
            $stmt->bindParam(':current_position', $currentPosition, \PDO::PARAM_INT);

            $result = $stmt->execute();
            $this->logger->info("addQueue() результат выполнения: " . ($result ? "успешно" : "ошибка"));

            return $result ? $this->db->lastInsertId() : false;
        } catch (\PDOException $e) {
            $this->logger->error("Error adding queue: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Обновление очереди
     */
    public function updateQueue($queueId, $queueData)
    {
        try {
            $this->logger->info("updateQueue() получил данные: ID=" . $queueId . ", данные=" . print_r($queueData, true));

            $old = $this->getQueueById($queueId);
            $stmt = $this->db->prepare("
                UPDATE queues
                SET name = :name, is_active = :is_active, max_load = :max_load, 
                    type = :type, algorithm = :algorithm
                WHERE id = :id
            ");
            
            $stmt->bindParam(':name', $queueData['name']);
            $stmt->bindParam(':type', $queueData['type']);
            $stmt->bindParam(':is_active', $queueData['is_active'], \PDO::PARAM_INT);
            $stmt->bindParam(':max_load', $queueData['max_load'], \PDO::PARAM_INT);
            $stmt->bindParam(':algorithm', $queueData['algorithm']);
            $stmt->bindParam(':id', $queueId, \PDO::PARAM_INT);

            $result = $stmt->execute();
            $this->logger->info("updateQueue() результат выполнения: " . ($result ? "успешно" : "ошибка"));

            if ($result && isset($old['max_load']) && $old['max_load'] != $queueData['max_load']) {
                $this->updateManagersMaxLoadForQueue($queueId, $queueData['max_load']);
            }

            return $result;
        } catch (\PDOException $e) {
            $this->logger->error("Error updating queue: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Обновление максимальной нагрузки для всех менеджеров в очереди
     */
    public function updateManagersMaxLoadForQueue($queueId, $maxLoad)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE queue_manager_relations 
                SET max_load = :max_load 
                WHERE queue_id = :queue_id
            ");
            
            $stmt->bindParam(':queue_id', $queueId, \PDO::PARAM_INT);
            $stmt->bindParam(':max_load', $maxLoad, \PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (\PDOException $e) {
            $this->logger->error("Error updating managers max load: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Удаление очереди
     */
    public function deleteQueue($queueId)
    {
        try {
            $this->logger->info("deleteQueue() получил ID: " . $queueId);

            // Начинаем транзакцию
            $this->db->beginTransaction();

            // Удаляем связи с менеджерами (используем правильное имя таблицы)
            $stmt = $this->db->prepare("
                DELETE FROM queue_manager_relations
                WHERE queue_id = :id
            ");
            $stmt->bindParam(':id', $queueId, \PDO::PARAM_INT);
            $stmt->execute();

            // Удаляем записи логов распределения
            $stmt = $this->db->prepare("
                DELETE FROM distribution_logs
                WHERE queue_id = :id
            ");
            $stmt->bindParam(':id', $queueId, \PDO::PARAM_INT);
            $stmt->execute();

            // Удаляем саму очередь
            $stmt = $this->db->prepare("
                DELETE FROM queues
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $queueId, \PDO::PARAM_INT);
            $result = $stmt->execute();

            if ($result) {
                $this->db->commit();
                $this->logger->info("deleteQueue() результат выполнения: успешно");
                return true;
            } else {
                $this->db->rollBack();
                $this->logger->error("deleteQueue() результат выполнения: ошибка");
                return false;
            }
        } catch (\PDOException $e) {
            $this->db->rollBack();
            $this->logger->error("Error deleting queue: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Сброс позиции очереди
     */
    public function resetQueuePosition($queueId)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE queues
                SET current_position = 0
                WHERE id = :id
            ");

            $stmt->bindParam(':id', $queueId, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            $this->logger->error("Error resetting queue position: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Увеличение нагрузки менеджера в очереди
     */
    public function incrementManagerLoad($queueId, $managerId)
    {
        try {
            // Проверяем, есть ли уже связь
            $stmt = $this->db->prepare("
                SELECT id FROM queue_manager_relations
                WHERE queue_id = :queue_id AND manager_id = :manager_id
            ");

            $stmt->bindParam(':queue_id', $queueId, \PDO::PARAM_INT);
            $stmt->bindParam(':manager_id', $managerId, \PDO::PARAM_INT);
            $stmt->execute();

            $relation = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($relation) {
                // Обновляем существующую связь
                $stmt = $this->db->prepare("
                    UPDATE queue_manager_relations
                    SET current_load = current_load + 1
                    WHERE queue_id = :queue_id AND manager_id = :manager_id
                ");
            } else {
                // Создаем новую связь
                $stmt = $this->db->prepare("
                    INSERT INTO queue_manager_relations
                    (queue_id, manager_id, max_load, current_load, is_active, priority)
                    VALUES
                    (:queue_id, :manager_id, 2, 1, 1, 10)
                ");
            }

            $stmt->bindParam(':queue_id', $queueId, \PDO::PARAM_INT);
            $stmt->bindParam(':manager_id', $managerId, \PDO::PARAM_INT);

            // Также увеличиваем общую нагрузку менеджера
            $managerStmt = $this->db->prepare("
                UPDATE managers
                SET current_load = current_load + 1
                WHERE id = :manager_id
            ");

            $managerStmt->bindParam(':manager_id', $managerId, \PDO::PARAM_INT);
            $managerStmt->execute();

            return $stmt->execute();
        } catch (\PDOException $e) {
            $this->logger->error("Error incrementing manager load: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Уменьшение нагрузки менеджера в очереди
     */
    public function decrementManagerLoad($queueId, $managerId)
    {
        try {
            // Уменьшаем нагрузку в очереди
            $stmt = $this->db->prepare("
                UPDATE queue_manager_relations
                SET current_load = GREATEST(current_load - 1, 0)
                WHERE queue_id = :queue_id AND manager_id = :manager_id
            ");

            $stmt->bindParam(':queue_id', $queueId, \PDO::PARAM_INT);
            $stmt->bindParam(':manager_id', $managerId, \PDO::PARAM_INT);

            // Также уменьшаем общую нагрузку менеджера
            $managerStmt = $this->db->prepare("
                UPDATE managers
                SET current_load = GREATEST(current_load - 1, 0)
                WHERE id = :manager_id
            ");

            $managerStmt->bindParam(':manager_id', $managerId, \PDO::PARAM_INT);
            $managerStmt->execute();

            return $stmt->execute();
        } catch (\PDOException $e) {
            $this->logger->error("Error decrementing manager load: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получить всех менеджеров в очереди
     */
    public function getManagersForQueue($queueId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    m.id,
                    m.name,
                    qmr.max_load,
                    qmr.current_load,
                    qmr.priority,
                    m.specializations
                FROM queue_manager_relations qmr
                JOIN managers m ON qmr.manager_id = m.id
                WHERE qmr.queue_id = :queue_id AND qmr.is_active = 1
                ORDER BY m.name ASC
            ");
            
            $stmt->bindParam(':queue_id', $queueId, \PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Error in getManagersForQueue: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Получение активных менеджеров для очереди (для алгоритмов распределения)
     */
    public function getActiveManagersForQueue($queueId)
    {
        return $this->getManagersForQueue($queueId);
    }

    /**
     * Настройка связи менеджера с очередью
     */
    public function setManagerQueueRelation($queueId, $managerId, $maxLoad, $isFallback = 0, $priority = 10)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO queue_manager_relations 
                    (queue_id, manager_id, max_load, is_active, is_fallback, priority) 
                VALUES 
                    (:queue_id, :manager_id, :max_load_insert, 1, :is_fallback, :priority)
                ON DUPLICATE KEY UPDATE 
                    max_load = :max_load_update,
                    is_active = 1,
                    is_fallback = :is_fallback_update,
                    priority = :priority_update
            ");
            
            $stmt->bindParam(':queue_id', $queueId, \PDO::PARAM_INT);
            $stmt->bindParam(':manager_id', $managerId, \PDO::PARAM_INT);
            $stmt->bindParam(':max_load_insert', $maxLoad, \PDO::PARAM_INT);
            $stmt->bindParam(':max_load_update', $maxLoad, \PDO::PARAM_INT);
            $stmt->bindParam(':is_fallback', $isFallback, \PDO::PARAM_INT);
            $stmt->bindParam(':is_fallback_update', $isFallback, \PDO::PARAM_INT);
            $stmt->bindParam(':priority', $priority, \PDO::PARAM_INT);
            $stmt->bindParam(':priority_update', $priority, \PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (\PDOException $e) {
            $this->logger->error("Error setting manager queue relation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Удаление менеджера из очереди
     */
    public function removeManagerFromQueue($queueId, $managerId)
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM queue_manager_relations 
                WHERE queue_id = :queue_id AND manager_id = :manager_id
            ");
            
            $stmt->bindParam(':queue_id', $queueId, \PDO::PARAM_INT);
            $stmt->bindParam(':manager_id', $managerId, \PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (\PDOException $e) {
            $this->logger->error("Error removing manager from queue: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Обновление позиции в очереди (для round-robin)
     */
    public function updateQueuePosition($queueId, $managerId)
    {
        try {
            // Получаем всех менеджеров очереди
            $managers = $this->getActiveManagersForQueue($queueId);
            
            // Если менеджеров нет, нечего обновлять
            if (empty($managers)) {
                return false;
            }

            // Находим индекс текущего менеджера
            $currentIndex = -1;
            foreach ($managers as $index => $manager) {
                if ($manager['id'] == $managerId) {
                    $currentIndex = $index;
                    break;
                }
            }

            // Вычисляем новую позицию
            $newPosition = ($currentIndex + 1) % count($managers);

            // Обновляем позицию в БД
            $stmt = $this->db->prepare("
                UPDATE queues 
                SET current_position = :position 
                WHERE id = :id
            ");
            
            $stmt->bindParam(':position', $newPosition, \PDO::PARAM_INT);
            $stmt->bindParam(':id', $queueId, \PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (\PDOException $e) {
            $this->logger->error("Error updating queue position: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Обновление приоритета менеджера в очереди
     */
    public function updateManagerPriority($queueId, $managerId, $priority)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE queue_manager_relations 
                SET priority = :priority 
                WHERE queue_id = :queue_id AND manager_id = :manager_id
            ");
            
            $stmt->bindParam(':priority', $priority, \PDO::PARAM_INT);
            $stmt->bindParam(':queue_id', $queueId, \PDO::PARAM_INT);
            $stmt->bindParam(':manager_id', $managerId, \PDO::PARAM_INT);
            
            $result = $stmt->execute();
            
            if ($result) {
                $this->logger->info("Обновлен приоритет менеджера в очереди: $queueId, менеджер: $managerId, приоритет: $priority");
            } else {
                $this->logger->error("Не удалось обновить приоритет менеджера");
            }
            
            return $result;
        } catch (\PDOException $e) {
            $this->logger->error("Error updating manager priority: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получение статистики распределения для очереди
     */
    public function getQueueDistributionStats($queueId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status != 'error' THEN 1 ELSE 0 END) as successful_distributions,
                    SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as failed_distributions
                FROM order_tracking
                WHERE queue_id = :queue_id
            ");
            
            $stmt->bindParam(':queue_id', $queueId, \PDO::PARAM_INT);
            $stmt->execute();
            
            $stats = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $stats ?: [
                'total_orders' => 0,
                'successful_distributions' => 0,
                'failed_distributions' => 0
            ];
        } catch (\PDOException $e) {
            $this->logger->error("Error getting queue distribution stats: " . $e->getMessage());
            return [
                'total_orders' => 0,
                'successful_distributions' => 0,
                'failed_distributions' => 0
            ];
        }
    }
}