<?php



use PDO;
use Exception;

class Distributor
{
    private $db;
    private $logModel;

    public function __construct(PDO $db, $logModel = null)
    {
        $this->db = $db;
        $this->logModel = $logModel;
    }

 /**
 * Распределяет заказ по соответствующему алгоритму
 * 
 * @param int $queueId ID очереди
 * @param string $orderId ID заказа
 * @return array|false Информация о назначении или false в случае ошибки
 */
public function distributeOrder($queueId, $orderId)
{
    try {
        // Получаем информацию об очереди
        $queue = $this->getQueueById($queueId);
        
        if (!$queue) {
            $this->logDistribution($queueId, null, $orderId, 'error', 'Очередь не найдена');
            return false;
        }
        
        if (!$queue['is_active']) {
            $this->logDistribution($queueId, null, $orderId, 'error', 'Очередь неактивна');
            return false;
        }
        
        // Вызываем соответствующий метод распределения в зависимости от типа очереди
        switch ($queue['type']) {
            case 'force':
                return $this->roundRobinDistribution($queue, $orderId);
            case 'online':
                return $this->onlineDistribution($queue, $orderId);
            case 'online_fallback':
                return $this->onlineFallbackDistribution($queue, $orderId);
            default:
                $this->logDistribution($queueId, null, $orderId, 'error', 'Неизвестный тип очереди: ' . $queue['type']);
                return false;
        }
    } catch (Exception $e) {
        $this->logDistribution($queueId, null, $orderId, 'error', 'Ошибка распределения: ' . $e->getMessage());
        return false;
    }
}

    /**
     * Алгоритм распределения Round Robin (Force)
     */
    private function roundRobinDistribution($queue, $orderId)
    {
        // Получаем активных менеджеров для очереди
        $managers = $this->getActiveManagersForQueue($queue['id']);
        
        if (empty($managers)) {
            $this->logDistribution($queue['id'], null, $orderId, 'error', 'Нет активных менеджеров в очереди');
            return false;
        }
        
        // Определяем следующего менеджера по позиции в очереди
        $position = $queue['current_position'];
        $totalManagers = count($managers);
        
        // Убеждаемся, что позиция находится в пределах массива
        if ($position >= $totalManagers) {
            $position = 0;
        }
        
        $manager = $managers[$position];
        
        // Проверяем, не превышена ли максимальная нагрузка менеджера в этой очереди
        if ($manager['queue_current_load'] >= $manager['queue_max_load']) {
            // Ищем следующего менеджера, который не перегружен
            $startPosition = $position;
            do {
                $position = ($position + 1) % $totalManagers;
                $manager = $managers[$position];
                
                if ($manager['queue_current_load'] < $manager['queue_max_load']) {
                    break;
                }
                
                // Если мы вернулись к начальной позиции, значит все менеджеры перегружены
                if ($position == $startPosition) {
                    $this->logDistribution($queue['id'], null, $orderId, 'error', 'Все менеджеры в очереди перегружены');
                    return false;
                }
            } while (true);
        }
        
        // Увеличиваем текущую позицию в очереди для следующего заказа
        $nextPosition = ($position + 1) % $totalManagers;
        $this->updateQueuePosition($queue['id'], $nextPosition);
        
        // Назначаем заказ менеджеру
        $result = $this->assignOrderToManager($queue['id'], $manager['id'], $orderId);
        
        if ($result) {
            $this->logDistribution(
                $queue['id'], 
                $manager['id'], 
                $orderId, 
                'success', 
                "Заказ назначен менеджеру {$manager['name']} по алгоритму Round Robin"
            );
            
            return [
                'queue_id' => $queue['id'],
                'queue_name' => $queue['name'],
                'manager_id' => $manager['id'],
                'manager_name' => $manager['name'],
                'algorithm' => 'round_robin',
                'order_id' => $orderId
            ];
        } else {
            $this->logDistribution(
                $queue['id'], 
                null, 
                $orderId, 
                'error', 
                "Ошибка при назначении заказа менеджеру {$manager['name']}"
            );
            
            return false;
        }
    }

    /**
     * Алгоритм распределения только между онлайн-менеджерами
     */
    private function onlineDistribution($queue, $orderId)
    {
        // Получаем активных менеджеров для очереди, которые онлайн
        $managers = $this->getOnlineManagersForQueue($queue['id']);
        
        if (empty($managers)) {
            $this->logDistribution(
                $queue['id'], 
                null, 
                $orderId, 
                'error', 
                'Нет активных онлайн-менеджеров в очереди'
            );
            return false;
        }
        
        // Определяем следующего менеджера по позиции в очереди
        $position = $queue['current_position'];
        $totalManagers = count($managers);
        
        // Убеждаемся, что позиция находится в пределах массива
        if ($position >= $totalManagers) {
            $position = 0;
        }
        
        $manager = $managers[$position];
        
        // Проверяем, не превышена ли максимальная нагрузка менеджера в этой очереди
        if ($manager['queue_current_load'] >= $manager['queue_max_load']) {
            // Ищем следующего онлайн-менеджера, который не перегружен
            $startPosition = $position;
            do {
                $position = ($position + 1) % $totalManagers;
                $manager = $managers[$position];
                
                if ($manager['queue_current_load'] < $manager['queue_max_load']) {
                    break;
                }
                
                // Если мы вернулись к начальной позиции, значит все менеджеры перегружены
                if ($position == $startPosition) {
                    $this->logDistribution(
                        $queue['id'], 
                        null, 
                        $orderId, 
                        'error', 
                        'Все онлайн-менеджеры в очереди перегружены'
                    );
                    return false;
                }
            } while (true);
        }
        
        // Увеличиваем текущую позицию в очереди для следующего заказа
        $nextPosition = ($position + 1) % $totalManagers;
        $this->updateQueuePosition($queue['id'], $nextPosition);
        
        // Назначаем заказ менеджеру
        $result = $this->assignOrderToManager($queue['id'], $manager['id'], $orderId);
        
        if ($result) {
            $this->logDistribution(
                $queue['id'], 
                $manager['id'], 
                $orderId, 
                'success', 
                "Заказ назначен онлайн-менеджеру {$manager['name']} по алгоритму Online"
            );
            
            return [
                'queue_id' => $queue['id'],
                'queue_name' => $queue['name'],
                'manager_id' => $manager['id'],
                'manager_name' => $manager['name'],
                'algorithm' => 'online',
                'order_id' => $orderId
            ];
        } else {
            $this->logDistribution(
                $queue['id'], 
                null, 
                $orderId, 
                'error', 
                "Ошибка при назначении заказа онлайн-менеджеру {$manager['name']}"
            );
            
            return false;
        }
    }

    /**
     * Алгоритм распределения с переключением на резервных менеджеров
     */
    private function onlineFallbackDistribution($queue, $orderId)
    {
        // Сначала пробуем назначить заказ по алгоритму Online
        $onlineManagers = $this->getOnlineManagersForQueue($queue['id']);
        
        if (!empty($onlineManagers)) {
            $position = $queue['current_position'];
            $totalManagers = count($onlineManagers);
            
            // Убеждаемся, что позиция находится в пределах массива
            if ($position >= $totalManagers) {
                $position = 0;
            }
            
            $manager = $onlineManagers[$position];
            
            // Проверяем, не превышена ли максимальная нагрузка менеджера
            if ($manager['queue_current_load'] < $manager['queue_max_load']) {
                // Увеличиваем текущую позицию в очереди для следующего заказа
                $nextPosition = ($position + 1) % $totalManagers;
                $this->updateQueuePosition($queue['id'], $nextPosition);
                
                // Назначаем заказ менеджеру
                $result = $this->assignOrderToManager($queue['id'], $manager['id'], $orderId);
                
                if ($result) {
                    $this->logDistribution(
                        $queue['id'], 
                        $manager['id'], 
                        $orderId, 
                        'success', 
                        "Заказ назначен онлайн-менеджеру {$manager['name']} по алгоритму Online-Fallback"
                    );
                    
                    return [
                        'queue_id' => $queue['id'],
                        'queue_name' => $queue['name'],
                        'manager_id' => $manager['id'],
                        'manager_name' => $manager['name'],
                        'algorithm' => 'online_fallback',
                        'order_id' => $orderId
                    ];
                }
            }
        }
        
        // Если нет онлайн менеджеров или все перегружены, используем fallback-менеджеров
        $fallbackManagers = $this->getFallbackManagersForQueue($queue['id']);
        
        if (empty($fallbackManagers)) {
            $this->logDistribution(
                $queue['id'], 
                null, 
                $orderId, 
                'error', 
                'Нет активных онлайн-менеджеров и резервных менеджеров в очереди'
            );
            return false;
        }
        
        // Используем Round Robin для fallback-менеджеров
        $position = $queue['current_position'] % count($fallbackManagers);
        $manager = $fallbackManagers[$position];
        
        // Проверяем, не превышена ли максимальная нагрузка менеджера
        if ($manager['queue_current_load'] >= $manager['queue_max_load']) {
            // Ищем следующего fallback-менеджера, который не перегружен
            $startPosition = $position;
            $totalFallback = count($fallbackManagers);
            
            do {
                $position = ($position + 1) % $totalFallback;
                $manager = $fallbackManagers[$position];
                
                if ($manager['queue_current_load'] < $manager['queue_max_load']) {
                    break;
                }
                
                // Если мы вернулись к начальной позиции, значит все fallback-менеджеры перегружены
                if ($position == $startPosition) {
                    $this->logDistribution(
                        $queue['id'], 
                        null, 
                        $orderId, 
                        'error', 
                        'Все резервные менеджеры в очереди перегружены'
                    );
                    return false;
                }
            } while (true);
        }
        
        // Увеличиваем текущую позицию в очереди для следующего заказа
        $nextPosition = ($position + 1) % count($fallbackManagers);
        $this->updateQueuePosition($queue['id'], $nextPosition);
        
        // Назначаем заказ менеджеру
        $result = $this->assignOrderToManager($queue['id'], $manager['id'], $orderId);
        
        if ($result) {
            $this->logDistribution(
                $queue['id'], 
                $manager['id'], 
                $orderId, 
                'success', 
                "Заказ назначен резервному менеджеру {$manager['name']} по алгоритму Online-Fallback"
            );
            
            return [
                'queue_id' => $queue['id'],
                'queue_name' => $queue['name'],
                'manager_id' => $manager['id'],
                'manager_name' => $manager['name'],
                'algorithm' => 'online_fallback (fallback mode)',
                'order_id' => $orderId
            ];
        } else {
            $this->logDistribution(
                $queue['id'], 
                null, 
                $orderId, 
                'error', 
                "Ошибка при назначении заказа резервному менеджеру {$manager['name']}"
            );
            
            return false;
        }
    }

    /**
     * Получение очереди по ID
     */
    private function getQueueById($queueId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM queues
                WHERE id = :id
            ");
            
            $stmt->bindParam(':id', $queueId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting queue by ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получение активных менеджеров для очереди
     */
    private function getActiveManagersForQueue($queueId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    m.*,
                    qmr.max_load as queue_max_load,
                    qmr.current_load as queue_current_load,
                    qmr.is_fallback
                FROM 
                    managers m
                JOIN 
                    queue_manager_relations qmr ON m.id = qmr.manager_id
                WHERE 
                    qmr.queue_id = :queue_id AND
                    m.is_active = 1
                ORDER BY 
                    m.id
            ");
            
            $stmt->bindParam(':queue_id', $queueId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting active managers for queue: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Получение онлайн-менеджеров для очереди
     */
    private function getOnlineManagersForQueue($queueId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    m.*,
                    qmr.max_load as queue_max_load,
                    qmr.current_load as queue_current_load,
                    qmr.is_fallback
                FROM 
                    managers m
                JOIN 
                    queue_manager_relations qmr ON m.id = qmr.manager_id
                WHERE 
                    qmr.queue_id = :queue_id AND
                    m.is_active = 1 AND
                    m.is_online = 1
                ORDER BY 
                    m.id
            ");
            
            $stmt->bindParam(':queue_id', $queueId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting online managers for queue: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Получение резервных менеджеров для очереди
     */
    private function getFallbackManagersForQueue($queueId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    m.*,
                    qmr.max_load as queue_max_load,
                    qmr.current_load as queue_current_load,
                    qmr.is_fallback
                FROM 
                    managers m
                JOIN 
                    queue_manager_relations qmr ON m.id = qmr.manager_id
                WHERE 
                    qmr.queue_id = :queue_id AND
                    m.is_active = 1 AND
                    qmr.is_fallback = 1
                ORDER BY 
                    m.id
            ");
            
            $stmt->bindParam(':queue_id', $queueId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting fallback managers for queue: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Обновление текущей позиции в очереди
     */
    private function updateQueuePosition($queueId, $position)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE queues
                SET current_position = :position
                WHERE id = :id
            ");
            
            $stmt->bindParam(':id', $queueId, PDO::PARAM_INT);
            $stmt->bindParam(':position', $position, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error updating queue position: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Назначение заказа менеджеру
     */
    private function assignOrderToManager($queueId, $managerId, $orderId)
    {
        try {
            $this->db->beginTransaction();
            
            // Увеличиваем текущую нагрузку менеджера в очереди
            $stmtUpdateQueueLoad = $this->db->prepare("
                UPDATE queue_manager_relations
                SET current_load = current_load + 1
                WHERE queue_id = :queue_id AND manager_id = :manager_id
            ");
            
            $stmtUpdateQueueLoad->bindParam(':queue_id', $queueId, PDO::PARAM_INT);
            $stmtUpdateQueueLoad->bindParam(':manager_id', $managerId, PDO::PARAM_INT);
            $stmtUpdateQueueLoad->execute();
            
            // Увеличиваем общую текущую нагрузку менеджера
            $stmtUpdateManagerLoad = $this->db->prepare("
                UPDATE managers
                SET current_load = current_load + 1
                WHERE id = :id
            ");
            
            $stmtUpdateManagerLoad->bindParam(':id', $managerId, PDO::PARAM_INT);
            $stmtUpdateManagerLoad->execute();
            
            // Здесь должен быть код для отправки информации о назначении во внешние системы
            // (Bitrix24, RetailCRM и т.д.)
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error assigning order to manager: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Запись в лог распределения
     */
    private function logDistribution($queueId, $managerId, $orderId, $status, $description)
    {
        if ($this->logModel) {
            $logData = [
                'queue_id' => $queueId,
                'manager_id' => $managerId,
                'order_id' => $orderId,
                'status' => $status,
                'description' => $description
            ];
            
            return $this->logModel->addLog($logData);
        } else {
            // Если модель лога не передана, просто пишем в error_log
            error_log("Distribution log: Queue ID: $queueId, Manager ID: $managerId, Order ID: $orderId, Status: $status, Description: $description");
            return true;
        }
    }
}