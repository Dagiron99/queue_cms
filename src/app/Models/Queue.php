<?php


class Queue
{
    private $db;
    private $lastQuery;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
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

            logToFile("getAllQueues() вернул " . count($result) . " строк", 'queue.log');

            return $result;
        } catch (\PDOException $e) {
            logToFile("Error getting all queues: " . $e->getMessage(), 'queue.log');
            return [];
        }
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
                logToFile("Invalid queue ID: " . $queueId, 'queue.log');
                return false;
            }

            $stmt = $this->db->prepare("
                SELECT * FROM queues
                WHERE id = :id
            ");

            $stmt->bindParam(':id', $queueId, \PDO::PARAM_INT);
            $stmt->execute();

            $this->lastQuery = $stmt->queryString;
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            logToFile("getQueueById($queueId) результат: " . ($result ? "найдена" : "не найдена"), 'queue.log');

            return $result;
        } catch (\PDOException $e) {
            logToFile("Error getting queue by ID: " . $e->getMessage(), 'queue.log');
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
            logToFile("addQueue() получил данные: " . print_r($queueData, true), 'queue.log');

            $stmt = $this->db->prepare("
                INSERT INTO queues (name, algorithm, is_active, max_load, type)
                VALUES (:name, :algorithm, :is_active, :max_load, :type)
            ");

            $stmt->bindParam(':name', $queueData['name']);
            $stmt->bindParam(':algorithm', $queueData['algorithm']);
            $stmt->bindParam(':is_active', $queueData['is_active'], \PDO::PARAM_INT);
            $stmt->bindParam(':max_load', $queueData['max_load'], \PDO::PARAM_INT);
            $stmt->bindParam(':type', $queueData['type']);

            $result = $stmt->execute();

            logToFile("addQueue() результат выполнения: " . ($result ? "успешно" : "ошибка"), 'queue.log');

            return $result ? $this->db->lastInsertId() : false;
        } catch (\PDOException $e) {
            logToFile("Error adding queue: " . $e->getMessage(), 'queue.log');
            return false;
        }
    }
    /**
     * Обновление очереди
     */
    public function updateQueue($queueId, $queueData)
    {
        try {
            logToFile("updateQueue() получил данные: ID=" . $queueId . ", данные=" . print_r($queueData, true), 'queue.log');

            $old = $this->getQueueById($queueId);
            $stmt = $this->db->prepare("
                UPDATE queues
                SET name = :name, is_active = :is_active, max_load = :max_load, type = :type
                WHERE id = :id
            ");
            $stmt->bindParam(':name', $queueData['name']);
            $stmt->bindParam(':type', $queueData['type']);
            $stmt->bindParam(':is_active', $queueData['is_active'], \PDO::PARAM_INT);
            $stmt->bindParam(':max_load', $queueData['max_load'], \PDO::PARAM_INT);
            $stmt->bindParam(':id', $queueId, \PDO::PARAM_INT);

            $result = $stmt->execute();

            logToFile("updateQueue() результат выполнения: " . ($result ? "успешно" : "ошибка"), 'queue.log');

            if ($result && isset($old['max_load']) && $old['max_load'] != $queueData['max_load']) {
                $this->updateManagersMaxLoadForQueue($queueId, $queueData['max_load']);
            }

            return $result;
        } catch (\PDOException $e) {
            logToFile("Error updating queue: " . $e->getMessage(), 'queue.log');
            return false;
        }
    }

    public function updateManagersMaxLoadForQueue($queueId, $maxLoad)
    {
        $stmt = $this->db->prepare("
            UPDATE queue_manager_relations SET max_load = :max_load WHERE queue_id = :queue_id
        ");
        $stmt->bindParam(':queue_id', $queueId, \PDO::PARAM_INT);
        $stmt->bindParam(':max_load', $maxLoad, \PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Удаление очереди
     */
    public function deleteQueue($queueId)
    {
        try {
            logToFile("deleteQueue() получил ID: " . $queueId, 'queue.log');

            // Начинаем транзакцию
            $this->db->beginTransaction();

            // Удаляем связи с менеджерами
            $stmt = $this->db->prepare("
                DELETE FROM queue_manager
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
                logToFile("deleteQueue() результат выполнения: успешно", 'queue.log');
                return true;
            } else {
                $this->db->rollBack();
                logToFile("deleteQueue() результат выполнения: ошибка", 'queue.log');
                return false;
            }
        } catch (\PDOException $e) {
            $this->db->rollBack();
            logToFile("Error deleting queue: " . $e->getMessage(), 'queue.log');
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
                SET current_position = 1,
                    updated_at = NOW()
                WHERE id = :id
            ");

            $stmt->bindParam(':id', $queueId, \PDO::PARAM_INT);

            return $stmt->execute();
        } catch (\PDOException $e) {
            logToFile("Error resetting queue position: " . $e->getMessage(), 'queue.log');
            return false;
        }
    }

    /**
     * Получение статистики распределения для очереди
     */
    public function getQueueDistributionStats($queueId)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM queues WHERE id = :id LIMIT 1");
            $stmt->bindParam(':id', $queueId, \PDO::PARAM_INT);
            $stmt->execute();

            $stats = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Проверка на NULL и установка значений по умолчанию
            $stats['total_orders'] = $stats['total_orders'] ?? 0;
            $stats['successful_distributions'] = $stats['successful_distributions'] ?? 0;
            $stats['failed_distributions'] = $stats['failed_distributions'] ?? 0;

            // Получаем статистику по менеджерам
            $stmt = $this->db->prepare("
                SELECT 
                    m.id,
                    m.name,
                    COUNT(dl.id) as orders
                FROM managers m
                JOIN distribution_logs dl ON m.id = dl.manager_id
                WHERE dl.queue_id = :queue_id AND dl.status = 'success'
                GROUP BY m.id, m.name
                ORDER BY orders DESC
            ");

            $stmt->bindParam(':queue_id', $queueId, \PDO::PARAM_INT);
            $stmt->execute();

            $managerStats = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $stats['managers'] = $managerStats;

            return $stats;
        } catch (\PDOException $e) {
            logToFile("Error getting queue distribution stats: " . $e->getMessage(), 'queue.log');
            return [
                'total_orders' => 0,
                'successful_distributions' => 0,
                'failed_distributions' => 0,
                'managers' => []
            ];
        }
    }


    /**
     * Получение активных менеджеров для очереди с их нагрузкой
     */
    public function getActiveManagersForQueue($queueId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    m.id,
                    m.name,
                    m.bitrix24_id,
                    m.retailcrm_id,
                    m.current_load,
                    m.max_load,
                    m.is_active,
                    COALESCE(qmr.max_load, 2) as queue_max_load,
                    COALESCE(qmr.current_load, 0) as queue_current_load
                FROM managers m
                LEFT JOIN queue_manager_relations qmr ON m.id = qmr.manager_id AND qmr.queue_id = :queue_id
                WHERE m.is_active = 1 AND (qmr.is_active = 1 OR qmr.id IS NULL)
                ORDER BY m.name ASC
            ");

            $stmt->bindParam(':queue_id', $queueId, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            logToFile("Error getting active managers for queue: " . $e->getMessage(), 'queue.log');
            return [];
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
                    (queue_id, manager_id, max_load, current_load, is_active)
                    VALUES
                    (:queue_id, :manager_id, 2, 1, 1)
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
            logToFile("Error incrementing manager load: " . $e->getMessage(), 'queue.log');
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
            logToFile("Error decrementing manager load: " . $e->getMessage(), 'queue.log');
            return false;
        }
    }

    /**
     * Обновление позиции очереди для Round Robin
     */
    public function updateQueuePosition($queueId, $position)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE queues
                SET current_position = :position
                WHERE id = :id
            ");

            $stmt->bindParam(':position', $position, \PDO::PARAM_INT);
            $stmt->bindParam(':id', $queueId, \PDO::PARAM_INT);

            return $stmt->execute();
        } catch (\PDOException $e) {
            logToFile("Error updating queue position: " . $e->getMessage(), 'queue.log');
            return false;
        }
    }
    /**
     * Получить всех менеджеров в очереди (активных)
     */
    public function getManagersForQueue($queueId)
    {
        try {
            $stmt = $this->db->prepare("
            SELECT 
                m.id,
                m.name,
                qmr.max_load,
                qmr.current_load
            FROM queue_manager_relations qmr
            JOIN managers m ON qmr.manager_id = m.id
            WHERE qmr.queue_id = :queue_id AND qmr.is_active = 1
            ORDER BY m.name ASC
        ");
            $stmt->bindParam(':queue_id', $queueId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            logToFile("Error in getManagersForQueue: " . $e->getMessage(), 'queue.log');
            return [];
        }
    }
    public function setManagerQueueRelation($queueId, $managerId, $maxLoad, $isFallback = 0)
    {
        try {
            $stmt = $this->db->prepare("
            INSERT INTO queue_manager_relations 
                (queue_id, manager_id, max_load, is_active, is_fallback) 
            VALUES 
                (:queue_id, :manager_id, :max_load_insert, 1, :is_fallback)
            ON DUPLICATE KEY UPDATE 
                max_load = :max_load_update,
                is_active = 1,
                is_fallback = :is_fallback_update
        ");
            $stmt->bindParam(':queue_id', $queueId, \PDO::PARAM_INT);
            $stmt->bindParam(':manager_id', $managerId, \PDO::PARAM_INT);
            $stmt->bindParam(':max_load_insert', $maxLoad, \PDO::PARAM_INT);
            $stmt->bindParam(':max_load_update', $maxLoad, \PDO::PARAM_INT);
            $stmt->bindParam(':is_fallback', $isFallback, \PDO::PARAM_INT);
            $stmt->bindParam(':is_fallback_update', $isFallback, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            logToFile("Error setting manager queue relation: " . $e->getMessage(), 'queue.log');
            return false;
        }
    }

    public function removeManagerFromQueue($queueId, $managerId)
    {
        $stmt = $this->db->prepare("DELETE FROM queue_manager_relations WHERE queue_id = :queue_id AND manager_id = :manager_id");
        $stmt->bindParam(':queue_id', $queueId, \PDO::PARAM_INT);
        $stmt->bindParam(':manager_id', $managerId, \PDO::PARAM_INT);
        return $stmt->execute();
    }

}