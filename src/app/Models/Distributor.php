<?php

namespace App\Models;   

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Distributor
{
    private $db;
    private $logger;
    private $queueModel;
    private $managerModel;
    private $orderTracker;

    public function __construct()
    {
        $this->logger = new Logger('distributor');

        $this->db = Database::getInstance()->getConnection();

        $this->queueModel = new Queue();
        $this->managerModel = new Manager();
        $this->orderTracker = new OrderTracker();
    }

    /**
     * Распределение заказа по очереди
     * 
     * @param array $orderData Данные заказа
     * @param int $queueId ID очереди
     * @return array Результат распределения
     */
    public function distributeOrder($orderData, $queueId)
    {
        // Логируем начало распределения
        $this->logger->info("Starting distribution for order {$orderData['order_id']} to queue $queueId");

        // Получаем информацию о очереди
        $queue = $this->queueModel->getQueueById($queueId);
        if (!$queue) {
            $this->logger->error("Queue $queueId not found");
            return [
                'success' => false,
                'message' => "Queue $queueId not found",
                'order_id' => $orderData['order_id']
            ];
        }

        // Проверяем, активна ли очередь
        if (!$queue['is_active']) {
            $this->logger->warning("Queue $queueId is not active");
            return [
                'success' => false,
                'message' => "Queue $queueId is not active",
                'order_id' => $orderData['order_id']
            ];
        }

        // Выбираем подходящего менеджера
        $manager = $this->selectManager($queueId, $queue['algorithm']);

        if (!$manager) {
            $this->logger->error("No suitable manager found for queue $queueId");
            return [
                'success' => false,
                'message' => "No suitable manager found",
                'order_id' => $orderData['order_id']
            ];
        }

        // Логируем результат выбора менеджера
        $this->logger->info("Selected manager #{$manager['id']} ({$manager['name']}) for order {$orderData['order_id']}");

        // Обновляем нагрузку менеджера
        $this->queueModel->incrementManagerLoad($queueId, $manager['id']);

        // Добавляем заказ в систему отслеживания
        $trackingData = [
            'order_id' => $orderData['order_id'],
            'platform' => $orderData['platform'] ?? 'unknown',
            'status' => 'new',
            'manager_id' => $manager['id'],
            'queue_id' => $queueId,
            'data' => $orderData
        ];

        $this->orderTracker->addOrder($trackingData);

        // Логируем запись о распределении
        $this->logDistribution($orderData['order_id'], $queueId, $manager['id'], 'success');

        return [
            'success' => true,
            'message' => "Order successfully distributed",
            'order_id' => $orderData['order_id'],
            'manager' => [
                'id' => $manager['id'],
                'name' => $manager['name'],
                'bitrix24_id' => $manager['bitrix24_id'],
                'retailcrm_id' => $manager['retailcrm_id']
            ]
        ];
    }

    /**
     * Выбор менеджера для распределения
     * 
     * @param int $queueId ID очереди
     * @param string $algorithm Алгоритм распределения
     * @return array|false Данные менеджера или false, если не найден
     */
    private function selectManager($queueId, $algorithm)
    {
        $this->logger->info("Selecting manager for queue $queueId using algorithm '$algorithm'");

        // Получаем активных менеджеров для очереди
        $managers = $this->queueModel->getActiveManagersForQueue($queueId);

        if (empty($managers)) {
            $this->logger->warning("No active managers found for queue $queueId");
            return false;
        }

        // Выбираем менеджера в зависимости от алгоритма
        switch ($algorithm) {
            case 'round_robin':
                return $this->roundRobinSelection($queueId, $managers);

            case 'least_busy':
                return $this->leastBusySelection($managers);

            case 'random':
                return $this->randomSelection($managers);

            default:
                $this->logger->warning("Unknown algorithm '$algorithm', using round robin as default");
                return $this->roundRobinSelection($queueId, $managers);
        }
    }

    /**
     * Алгоритм распределения Round Robin
     */
    private function roundRobinSelection($queueId, $managers)
    {
        $this->logger->info("Using Round Robin algorithm for queue $queueId");

        // Получаем текущую позицию в очереди
        $queue = $this->queueModel->getQueueById($queueId);
        $currentPosition = $queue['current_position'];

        // Если позиция больше количества менеджеров, сбрасываем её
        if ($currentPosition > count($managers)) {
            $currentPosition = 1;
        }

        // Выбираем менеджера по текущей позиции
        $selectedManager = $managers[$currentPosition - 1];

        // Проверяем, не превышена ли максимальная нагрузка менеджера
        if ($selectedManager['queue_current_load'] >= $selectedManager['queue_max_load']) {
            $this->logger->warning("Manager #{$selectedManager['id']} has reached maximum load, trying next");

            // Ищем следующего доступного менеджера
            $found = false;
            $startPosition = $currentPosition;

            do {
                $currentPosition++;
                if ($currentPosition > count($managers)) {
                    $currentPosition = 1;
                }

                if ($currentPosition == $startPosition) {
                    $this->logger->error("All managers are at maximum load");
                    return false;
                }

                $selectedManager = $managers[$currentPosition - 1];

                if ($selectedManager['queue_current_load'] < $selectedManager['queue_max_load']) {
                    $found = true;
                }
            } while (!$found);
        }

        // Обновляем позицию в очереди
        $nextPosition = $currentPosition + 1;
        if ($nextPosition > count($managers)) {
            $nextPosition = 1;
        }

        $this->queueModel->updateQueuePosition($queueId, $nextPosition);

        return $selectedManager;
    }

    /**
     * Алгоритм распределения Least Busy
     */
    private function leastBusySelection($managers)
    {
        $this->logger->info("Using Least Busy algorithm");

        // Сортируем менеджеров по текущей нагрузке
        usort($managers, function ($a, $b) {
            $aLoadPercent = ($a['queue_current_load'] / $a['queue_max_load']) * 100;
            $bLoadPercent = ($b['queue_current_load'] / $b['queue_max_load']) * 100;

            return $aLoadPercent - $bLoadPercent;
        });

        // Проверяем, не превышена ли максимальная нагрузка у наименее загруженного менеджера
        $selectedManager = $managers[0];

        if ($selectedManager['queue_current_load'] >= $selectedManager['queue_max_load']) {
            $this->logger->error("All managers are at maximum load");
            return false;
        }

        return $selectedManager;
    }

    /**
     * Алгоритм случайного распределения
     */
    private function randomSelection($managers)
    {
        $this->logger->info("Using Random algorithm");

        // Отфильтровываем менеджеров, у которых не превышена максимальная нагрузка
        $availableManagers = array_filter($managers, function ($manager) {
            return $manager['queue_current_load'] < $manager['queue_max_load'];
        });

        if (empty($availableManagers)) {
            $this->logger->error("All managers are at maximum load");
            return false;
        }

        // Выбираем случайного менеджера
        $randomIndex = array_rand($availableManagers);
        return $availableManagers[$randomIndex];
    }

    /**
     * Логирование распределения
     */
    private function logDistribution($orderId, $queueId, $managerId, $status)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO distribution_logs (
                    order_id,
                    queue_id,
                    manager_id,
                    status,
                    created_at
                ) VALUES (
                    :order_id,
                    :queue_id,
                    :manager_id,
                    :status,
                    NOW()
                )
            ");

            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_STR);
            $stmt->bindParam(':queue_id', $queueId, PDO::PARAM_INT);
            $stmt->bindParam(':manager_id', $managerId, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);

            return $stmt->execute();
        } catch (\PDOException $e) {
            $this->logger->error("Error logging distribution: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получение статистики распределения
     */
    public function getDistributionStats($period = 'day', $limit = 7)
    {
        try {
            $dateFormat = '';
            $groupBy = '';

            switch ($period) {
                case 'hour':
                    $dateFormat = '%Y-%m-%d %H:00';
                    $groupBy = "DATE_FORMAT(created_at, '%Y-%m-%d %H:00')";
                    break;
                case 'day':
                    $dateFormat = '%Y-%m-%d';
                    $groupBy = "DATE(created_at)";
                    break;
                case 'week':
                    $dateFormat = '%Y-%u';
                    $groupBy = "YEARWEEK(created_at)";
                    break;
                case 'month':
                    $dateFormat = '%Y-%m';
                    $groupBy = "DATE_FORMAT(created_at, '%Y-%m')";
                    break;
                default:
                    $dateFormat = '%Y-%m-%d';
                    $groupBy = "DATE(created_at)";
            }

            $stmt = $this->db->prepare("
                SELECT 
                    DATE_FORMAT(created_at, :date_format) as period,
                    COUNT(*) as count,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful,
                    SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as failed
                FROM distribution_logs
                GROUP BY $groupBy
                ORDER BY period DESC
                LIMIT :limit
            ");

            $stmt->bindParam(':date_format', $dateFormat, PDO::PARAM_STR);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error("Error getting distribution stats: " . $e->getMessage());
            return [];
        }
    }
}