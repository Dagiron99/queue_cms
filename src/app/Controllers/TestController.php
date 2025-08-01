<?php

class TestController
{
    private $queueModel;
    private $managerModel;
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->queueModel = new Queue();
        $this->managerModel = new Manager();
    }

    /**
     * Метод для отображения страницы тестирования без запуска распределения
     */
    public function index(): void
    {
        // Получаем список очередей для выбора
        $queues = $this->queueModel->getAllQueues();
        
        // Инициализируем пустые массивы для шаблона
        $results = [];
        $queueInfo = null;
        $managers = [];
        $managerStats = [];
        
        // Отображаем страницу тестирования без результатов
        require __DIR__ . '/../../resources/views/tests/index.php';
    }
 
    public function testDistribution(): void
    {
        $queues = $this->queueModel->getAllQueues();
        $results = [];
        $queueInfo = null;
        $managers = [];
        $managerStats = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['queue_id'])) {
            $queueId = (int)$_POST['queue_id'];
            $orderCount = min(max((int)$_POST['order_count'], 1), 100);
            
            $queueInfo = $this->queueModel->getQueueById($queueId);
            
            if ($queueInfo) {
                $managers = $this->queueModel->getActiveManagersForQueue($queueId);
                
                // Инициализируем статистику только для существующих менеджеров
                foreach ($managers as $manager) {
                    if (isset($manager['id'])) {
                        $managerStats[$manager['id']] = [
                            'name' => $manager['name'] ?? 'Неизвестно',
                            'is_online' => (bool)($manager['is_online'] ?? false),
                            'is_fallback' => (bool)($manager['is_fallback'] ?? false),
                            'orders' => 0
                        ];
                    }
                }

                $distributor = new Distributor($this->db);
                $this->resetManagersLoad($queueId);
                
                for ($i = 0; $i < $orderCount; $i++) {
                    $orderId = 'TEST-ORDER-' . ($i + 1);
                    $result = $distributor->distributeOrder($queueId, $orderId);

                    // Проверяем существование ключей перед использованием
                    if ($result !== false && isset($result['manager_id']) && isset($managerStats[$result['manager_id']])) {
                        $managerStats[$result['manager_id']]['orders']++;
                        $result['algorithm'] = $queueInfo['type'] ?? 'unknown';
                    }
                    
                    $results[] = $result;
                }
                
                $this->resetManagersLoad($queueId);
            }
        }
        
        require __DIR__ . '/../../resources/views/tests/index.php';
    }
    
    private function resetManagersLoad(int $queueId): bool
    {
        try {
            $db = $this->db;
            
            // Сбрасываем нагрузку менеджеров в очереди
            $stmt = $db->prepare("
                UPDATE queue_manager_relations
                SET current_load = 0
                WHERE queue_id = :queue_id
            ");
            $stmt->bindValue(':queue_id', $queueId, PDO::PARAM_INT);
            $stmt->execute();
            
            // Обновляем общую нагрузку менеджеров
            $stmt = $db->prepare("
                UPDATE managers m
                SET current_load = (
                    SELECT COALESCE(SUM(qmr.current_load), 0)
                    FROM queue_manager_relations qmr
                    WHERE qmr.manager_id = m.id
                )
            ");
            $stmt->execute();
            
            return true;
        } catch (PDOException $e) {
            error_log("Error resetting managers load: " . $e->getMessage());
            return false;
        }
    }
}