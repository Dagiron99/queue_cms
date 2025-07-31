<?php


class Manager
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Получение всех менеджеров
     */
    public function getAllManagers()
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    id,
                    name,
                    bitrix24_id,
                    retailcrm_id,
                    status,
                    COALESCE(current_load, 0) AS current_load,
                    COALESCE(is_active, 0) AS is_active,
                    created_at,
                    updated_at
                FROM managers
                ORDER BY name ASC
            ");

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            logToFile("Error getting all managers: " . $e->getMessage(), 'app.log');
            return [];
        }
    }

    /**
     * Получение менеджера по ID
     */
    public function getById($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM managers WHERE id = :id");
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            logToFile("Error getting manager by ID: " . $e->getMessage(), 'app.log');
            return false;
        }
    }

    /**
     * Получение активных менеджеров
     */
    public function getActiveManagers()
    {
        try {
            $stmt = $this->db->query("
                SELECT * FROM managers
                WHERE is_active = 1
                ORDER BY name ASC
            ");

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            logToFile("Error getting active managers: " . $e->getMessage(), 'app.log');
            return [];
        }
    }

    /**
     * Получение неактивных менеджеров
     */
    public function getInactiveManagers()
    {
        try {
            $stmt = $this->db->query("
                SELECT * FROM managers
                WHERE is_active = 0
                ORDER BY name ASC
            ");

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            logToFile("Error getting inactive managers: " . $e->getMessage(), 'app.log');
            return [];
        }
    }

    /**
     * Добавление нового менеджера
     */
    public function addManager($data)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO managers (name, bitrix24_id, retailcrm_id, is_active, created_at)
                VALUES (:name, :bitrix24_id, :retailcrm_id, :is_active, NOW())
            ");

            $name = $data['name'];
            $bitrix24_id = !empty($data['bitrix24_id']) ? (int) $data['bitrix24_id'] : null;
            $retailcrm_id = !empty($data['retailcrm_id']) ? (int) $data['retailcrm_id'] : null;
            $is_active = isset($data['is_active']) ? 1 : 0;

            $stmt->bindParam(':name', $name, \PDO::PARAM_STR);
            $stmt->bindParam(':bitrix24_id', $bitrix24_id, \PDO::PARAM_INT);
            $stmt->bindParam(':retailcrm_id', $retailcrm_id, \PDO::PARAM_INT);
            $stmt->bindParam(':is_active', $is_active, \PDO::PARAM_INT);

            return $stmt->execute();
        } catch (\PDOException $e) {
            logToFile("Error adding manager: " . $e->getMessage(), 'app.log');
            return false;
        }
    }

    /**
     * Обновление менеджера
     */
    public function updateManager($id, $data)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE managers
                SET name = :name,
                    bitrix24_id = :bitrix24_id,
                    retailcrm_id = :retailcrm_id,
                    is_active = :is_active,
                    updated_at = NOW()
                WHERE id = :id
            ");

            $name = $data['name'];
            $bitrix24_id = !empty($data['bitrix24_id']) ? (int) $data['bitrix24_id'] : null;
            $retailcrm_id = !empty($data['retailcrm_id']) ? (int) $data['retailcrm_id'] : null;
            $is_active = isset($data['is_active']) ? 1 : 0;

            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->bindParam(':name', $name, \PDO::PARAM_STR);
            $stmt->bindParam(':bitrix24_id', $bitrix24_id, \PDO::PARAM_INT);
            $stmt->bindParam(':retailcrm_id', $retailcrm_id, \PDO::PARAM_INT);
            $stmt->bindParam(':is_active', $is_active, \PDO::PARAM_INT);

            return $stmt->execute();
        } catch (\PDOException $e) {
            logToFile("Error updating manager: " . $e->getMessage(), 'app.log');
            return false;
        }
    }

    /**
     * Удаление менеджера
     */
    public function deleteManager($id)
    {
        try {
            // Начинаем транзакцию
            $this->db->beginTransaction();

            // 1. Удаляем связанные записи из distribution_logs
            $stmt = $this->db->prepare("DELETE FROM distribution_logs WHERE manager_id = :id");
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();

            // 2. Удаляем связи менеджера с очередями
            $stmt = $this->db->prepare("DELETE FROM queue_manager WHERE manager_id = :id");
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();

            // 3. Теперь удаляем самого менеджера
            $stmt = $this->db->prepare("DELETE FROM managers WHERE id = :id");
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();

            // Если все операции выполнены успешно, фиксируем транзакцию
            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            // Если возникла ошибка, откатываем все изменения
            $this->db->rollBack();
            logToFile("Error deleting manager: " . $e->getMessage(), 'app.log');
            return false;
        }
    }

    /**
     * Обновление только статуса менеджера
     */
    public function updateStatus($id, $status)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE managers 
                SET status = :status
                WHERE id = :id
            ");

            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->bindParam(':status', $status, \PDO::PARAM_STR);

            return $stmt->execute();
        } catch (\PDOException $e) {
            logToFile("Error updating manager status: " . $e->getMessage(), 'app.log');
            return false;
        }
    }

    /**
     * Получение статистики распределения по дням
     */
    public function getDistributionStatsByDay($days = 7)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(id) as count
                FROM distribution_logs
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ");

            $stmt->bindParam(':days', $days, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            logToFile("Error getting distribution stats by day: " . $e->getMessage(), 'app.log');
            return [];
        }
    }

    /**
     * Получение статуса менеджера в очереди
     */
    public function getManagerFallbackStatus($managerId, $queueId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT is_fallback 
                FROM queue_manager 
                WHERE manager_id = :manager_id AND queue_id = :queue_id
            ");

            $stmt->bindParam(':manager_id', $managerId, \PDO::PARAM_INT);
            $stmt->bindParam(':queue_id', $queueId, \PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ? $result['is_fallback'] : 0;
        } catch (\PDOException $e) {
            logToFile("Error getting manager fallback status: " . $e->getMessage(), 'app.log');
            return 0;
        }
    }
    public function getAvailableManagersForQueue($queueId)
    {
        // Получить менеджеров, которые активны и не состоят в очереди $queueId
        $stmt = $this->db->prepare("
            SELECT m.id, m.name
            FROM managers m
            WHERE m.is_active = 1
              AND m.id NOT IN (
                  SELECT manager_id FROM queue_manager_relations WHERE queue_id = :queue_id AND is_active = 1
              )
            ORDER BY m.name
        ");
        $stmt->bindParam(':queue_id', $queueId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getAvailableManagers()
    {
        $stmt = $this->db->query("SELECT id, name FROM managers WHERE is_active = 1 ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}