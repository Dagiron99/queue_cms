<?php

namespace App\Services;

use App\Models\Queue;
use App\Models\Manager;

class QueueService
{
    private $logger;
    
    public function __construct()
    {
        $this->logger = new Logger('queue.log');
    }
    
    /**
     * Получение следующего менеджера из очереди
     */
    public function getNextManager($lineId, $orderId)
    {
        try {
            $queue = new Queue();
            $queueData = $queue->getQueueById($lineId);
            
            if (!$queueData) {
                $this->logger->error("Queue not found: $lineId");
                return [
                    'success' => false,
                    'message' => 'Queue not found'
                ];
            }
            
            if (!$queueData['is_active']) {
                $this->logger->warning("Queue is inactive: $lineId");
                return [
                    'success' => false,
                    'message' => 'Queue is inactive'
                ];
            }
            
            $managers = $queue->getActiveManagersForQueue($lineId);
            
            if (empty($managers)) {
                $this->logger->warning("No active managers in queue: $lineId");
                return [
                    'success' => false,
                    'message' => 'No active managers in queue'
                ];
            }
            
            // Выбор метода распределения
            $algorithm = $queueData['algorithm'] ?? 'round_robin';
            
            switch ($algorithm) {
                case 'round_robin':
                    $managerId = $this->roundRobinSelection($queueData, $managers);
                    break;
                    
                case 'load_balanced':
                    $managerId = $this->loadBalancedSelection($managers);
                    break;
                    
                case 'priority_based':
                    $managerId = $this->priorityBasedSelection($managers);
                    break;
                    
                default:
                    $managerId = $this->roundRobinSelection($queueData, $managers);
            }
            
            if (!$managerId) {
                $this->logger->error("Failed to select manager for queue: $lineId");
                return [
                    'success' => false,
                    'message' => 'Failed to select manager'
                ];
            }
            
            // Обновляем позицию в очереди
            $queue->updateQueuePosition($lineId);
            
            // Логируем распределение
            $queue->logDistribution($lineId, $orderId, $managerId);
            
            return [
                'success' => true,
                'manager_id' => $managerId
            ];
            
        } catch (\Exception $e) {
            $this->logger->error("Error getting next manager: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Internal error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Алгоритм Round Robin для выбора менеджера
     */
    private function roundRobinSelection($queueData, $managers)
    {
        $position = $queueData['current_position'] ?? 0;
        $totalManagers = count($managers);
        
        if ($totalManagers === 0) {
            return null;
        }
        
        // Если позиция за пределами массива, начинаем сначала
        if ($position >= $totalManagers) {
            $position = 0;
        }
        
        return $managers[$position]['id'];
    }
    
    /**
     * Алгоритм балансировки нагрузки для выбора менеджера
     */
    private function loadBalancedSelection($managers)
    {
        // Находим менеджера с наименьшим количеством активных заказов
        $minLoad = PHP_INT_MAX;
        $selectedManagerId = null;
        
        foreach ($managers as $manager) {
            $load = $manager['active_orders'] ?? 0;
            
            if ($load < $minLoad) {
                $minLoad = $load;
                $selectedManagerId = $manager['id'];
            }
        }
        
        return $selectedManagerId;
    }
    
    /**
     * Алгоритм выбора по приоритету
     */
    private function priorityBasedSelection($managers)
    {
        // Сортируем менеджеров по приоритету (если есть)
        usort($managers, function($a, $b) {
            $priorityA = $a['priority'] ?? 0;
            $priorityB = $b['priority'] ?? 0;
            
            return $priorityB - $priorityA; // По убыванию
        });
        
        // Возвращаем ID первого менеджера с наивысшим приоритетом
        return $managers[0]['id'] ?? null;
    }
}