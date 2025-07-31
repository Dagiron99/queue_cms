<?php

namespace App\Models;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Services\DatabaseService;

class Manager
{
    private $db;
    private $logger;


    public function __construct()
    {
        $this->logger = new Logger('manager');

        $this->db = DatabaseService::getInstance()->getConnection();
    }

    /**
     * Получение всех менеджеров
     */
    public function getAllManagers()
    {
        try {
            $db = $this->db;
            $query = "SELECT * FROM managers WHERE is_active = 1"; // Проблема может быть здесь
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            // Добавьте логирование
            $this->logger->error("Error getting managers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Получение всех менеджеров (алиас для совместимости)
     */
    public function getAll()
    {
        return $this->getAllManagers();
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
            $this->logger->error("Error getting manager by ID: " . $e->getMessage());
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
            $this->logger->error("Error getting active managers: " . $e->getMessage());
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
            $this->logger->error("Error getting inactive managers: " . $e->getMessage());
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
                INSERT INTO managers (
                    name, 
                    bitrix24_id, 
                    retailcrm_id, 
                    is_active, 
                    specializations,
                    created_at
                )
                VALUES (
                    :name, 
                    :bitrix24_id, 
                    :retailcrm_id, 
                    :is_active, 
                    :specializations,
                    NOW()
                )
            ");

            $name = $data['name'];
            $bitrix24_id = !empty($data['bitrix24_id']) ? (int) $data['bitrix24_id'] : null;
            $retailcrm_id = !empty($data['retailcrm_id']) ? (int) $data['retailcrm_id'] : null;
            $is_active = isset($data['is_active']) ? 1 : 0;
            $specializations = isset($data['specializations']) ? json_encode($data['specializations']) : '[]';

            $stmt->bindParam(':name', $name, \PDO::PARAM_STR);
            $stmt->bindParam(':bitrix24_id', $bitrix24_id, \PDO::PARAM_INT);
            $stmt->bindParam(':retailcrm_id', $retailcrm_id, \PDO::PARAM_INT);
            $stmt->bindParam(':is_active', $is_active, \PDO::PARAM_INT);
            $stmt->bindParam(':specializations', $specializations, \PDO::PARAM_STR);

            $result = $stmt->execute();
            return $result ? $this->db->lastInsertId() : false;
        } catch (\PDOException $e) {
            $this->logger->error("Error adding manager: " . $e->getMessage());
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
            $this->logger->error("Error updating manager: " . $e->getMessage());
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
            $stmt = $this->db->prepare("DELETE FROM queue_manager_relations WHERE manager_id = :id");
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();

            // 3. Удаляем специализации менеджера
            $stmt = $this->db->prepare("DELETE FROM manager_specializations WHERE manager_id = :id");
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();

            // 4. Теперь удаляем самого менеджера
            $stmt = $this->db->prepare("DELETE FROM managers WHERE id = :id");
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();

            // Если все операции выполнены успешно, фиксируем транзакцию
            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            // Если возникла ошибка, откатываем все изменения
            $this->db->rollBack();
            $this->logger->error("Error deleting manager: " . $e->getMessage());
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
                SET status = :status,
                    updated_at = NOW()
                WHERE id = :id
            ");

            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->bindParam(':status', $status, \PDO::PARAM_STR);

            return $stmt->execute();
        } catch (\PDOException $e) {
            $this->logger->error("Error updating manager status: " . $e->getMessage());
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
            $this->logger->error("Error getting distribution stats by day: " . $e->getMessage());
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
                FROM queue_manager_relations 
                WHERE manager_id = :manager_id AND queue_id = :queue_id
            ");

            $stmt->bindParam(':manager_id', $managerId, \PDO::PARAM_INT);
            $stmt->bindParam(':queue_id', $queueId, \PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ? $result['is_fallback'] : 0;
        } catch (\PDOException $e) {
            $this->logger->error("Error getting manager fallback status: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Получение доступных менеджеров для очереди
     */
    public function getAvailableManagersForQueue($queueId)
    {
        try {
            // Получить менеджеров, которые активны и не состоят в очереди $queueId
            $stmt = $this->db->prepare("
                SELECT m.id, m.name, m.specializations
                FROM managers m
                WHERE m.is_active = 1
                  AND m.id NOT IN (
                      SELECT manager_id FROM queue_manager_relations WHERE queue_id = :queue_id AND is_active = 1
                  )
                ORDER BY m.name
            ");
            $stmt->bindParam(':queue_id', $queueId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Error getting available managers for queue: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Получение всех доступных менеджеров
     */
    public function getAvailableManagers()
    {
        try {
            $stmt = $this->db->query("
                SELECT id, name, specializations 
                FROM managers 
                WHERE is_active = 1 
                ORDER BY name
            ");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Error getting available managers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Обновление специализаций менеджера
     */
    public function updateSpecializations($managerId, $specializations)
    {
        try {
            // Начинаем транзакцию
            $this->db->beginTransaction();
            
            // Удаляем старые специализации
            $stmt = $this->db->prepare("DELETE FROM manager_specializations WHERE manager_id = :manager_id");
            $stmt->bindParam(':manager_id', $managerId, \PDO::PARAM_INT);
            $stmt->execute();
            
            // Добавляем новые специализации
            if (!empty($specializations)) {
                $insertStmt = $this->db->prepare("
                    INSERT INTO manager_specializations (manager_id, category, created_at) 
                    VALUES (:manager_id, :category, NOW())
                ");
                
                foreach ($specializations as $category) {
                    $insertStmt->bindParam(':manager_id', $managerId, \PDO::PARAM_INT);
                    $insertStmt->bindParam(':category', $category, \PDO::PARAM_STR);
                    $insertStmt->execute();
                }
            }
            
            // Обновляем JSON-представление в таблице менеджеров для быстрого доступа
            $specJson = json_encode($specializations);
            $updateStmt = $this->db->prepare("
                UPDATE managers 
                SET specializations = :specializations,
                    updated_at = NOW()
                WHERE id = :manager_id
            ");
            $updateStmt->bindParam(':specializations', $specJson, \PDO::PARAM_STR);
            $updateStmt->bindParam(':manager_id', $managerId, \PDO::PARAM_INT);
            $updateStmt->execute();
            
            // Завершаем транзакцию
            $this->db->commit();
            
            $this->logger->info("Специализации менеджера ID:$managerId успешно обновлены");
            return true;
        } catch (\Exception $e) {
            // Откатываем транзакцию в случае ошибки
            $this->db->rollBack();
            $this->logger->error("Ошибка при обновлении специализаций: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получение всех категорий для специализаций
     */
    public function getAllCategories()
    {
        // Это может быть статический список или данные из другой таблицы
        return [
            'Электроника',
            'Одежда',
            'Бытовая техника',
            'Мебель',
            'Продукты питания',
            'Косметика',
            'Спортивные товары',
            'Детские товары',
            'Автотовары',
            'Книги и канцтовары'
        ];
    }
}