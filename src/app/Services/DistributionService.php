<?php


class DistributionService
{
    private $queue;
    private $manager;
    private $orderTracker;
    private $logger;

    public function __construct()
    {
        $this->queue = new Queue();
        $this->manager = new Manager();
        $this->orderTracker = new OrderTracker();
        $this->logger = new Logger('distribution_service');
    }

    /**
     * Обработка заказа и назначение менеджера
     * 
     * @param array $orderData Данные заказа
     * @param string $source Источник заказа (api, bitrix24, retailcrm)
     * @return array Результат обработки
     */
    public function processOrder($orderData, $source = 'api')
    {
        $this->logger->info("Обработка заказа: {$orderData['order_id']} из {$source}");

        // 1. Определяем тип очереди на основе данных заказа
        $queueType = $this->determineQueueType($orderData, $source);
        $this->logger->info("Определен тип очереди: {$queueType}");

        // 2. Получаем очередь по типу
        $queue = $this->queue->getQueueByType($queueType);
        if (!$queue) {
            $this->logger->error("Очередь типа {$queueType} не найдена");
            return [
                'success' => false,
                'message' => "Queue of type {$queueType} not found"
            ];
        }

        // 3. Получаем активных менеджеров для очереди
        $managers = $this->queue->getActiveManagersForQueue($queue['id']);
        if (empty($managers)) {
            $this->logger->error("Нет активных менеджеров в очереди {$queue['name']}");
            return [
                'success' => false,
                'message' => "No active managers in queue {$queue['name']}"
            ];
        }

        // 4. Выбираем менеджера по алгоритму очереди
        $selectedManager = $this->selectManagerByAlgorithm(
            $managers,
            $queue['algorithm'],
            $queue['id'],
            $orderData
        );

        if (!$selectedManager) {
            $this->logger->error("Не удалось выбрать менеджера для заказа {$orderData['order_id']}");
            return [
                'success' => false,
                'message' => "Failed to select manager"
            ];
        }

        // 5. Сохраняем информацию о назначении
        $this->orderTracker->trackOrder(
            $orderData['order_id'],
            $selectedManager['id'],
            $queue['id'],
            json_encode($orderData),
            'assigned'
        );

        // 6. Обновляем счетчик позиции в очереди, если используется round robin
        if ($queue['algorithm'] === 'round_robin') {
            $this->queue->updateQueuePosition($queue['id'], $selectedManager['id']);
        }

        $this->logger->info("Заказ {$orderData['order_id']} назначен менеджеру {$selectedManager['name']} (ID: {$selectedManager['id']})");

        // 7. Формируем ответ
        return [
            'success' => true,
            'queue' => [
                'id' => $queue['id'],
                'name' => $queue['name'],
                'type' => $queueType
            ],
            'manager' => [
                'id' => $selectedManager['id'],
                'name' => $selectedManager['name'],
                'email' => $selectedManager['email'],
                'phone' => $selectedManager['phone']
            ],
            'order_id' => $orderData['order_id']
        ];
    }

    /**
     * Определение типа очереди на основе данных заказа
     */
    private function determineQueueType($orderData, $source)
    {
        // Логика определения типа очереди на основе данных заказа
        
        // Если явно указан тип очереди в запросе, используем его
        if (isset($orderData['queue_type'])) {
            return $orderData['queue_type'];
        }

        // Правила определения типа очереди
        
        // 1. Force - срочные/принудительные заказы
        if (isset($orderData['priority']) && $orderData['priority'] === 'high') {
            return 'force';
        }
        
        if (isset($orderData['is_urgent']) && $orderData['is_urgent']) {
            return 'force';
        }
        
        // 2. Online-wellback - онлайн заказы с запросом на обратный звонок
        if (isset($orderData['callback_requested']) && $orderData['callback_requested']) {
            return 'online-wellback';
        }
        
        if (isset($orderData['channel']) && $orderData['channel'] === 'online' &&
            isset($orderData['contact_request']) && $orderData['contact_request'] === 'callback') {
            return 'online-wellback';
        }
        
        // 3. Для заказов из Bitrix24 с особыми полями
        if ($source === 'bitrix24') {
            $rawData = $orderData['raw_data'] ?? [];
            
            // Проверяем особые поля Bitrix
            if (isset($rawData['UF_CRM_URGENT']) && $rawData['UF_CRM_URGENT'] === 'Y') {
                return 'force';
            }
            
            if (isset($rawData['UF_CRM_CALLBACK']) && $rawData['UF_CRM_CALLBACK'] === 'Y') {
                return 'online-wellback';
            }
        }
        
        // По умолчанию - обычная онлайн очередь
        return 'online';
    }

    /**
     * Выбор менеджера по алгоритму очереди
     */
    private function selectManagerByAlgorithm($managers, $algorithm, $queueId, $orderData)
    {
        $this->logger->info("Выбор менеджера по алгоритму: {$algorithm}");
        
        switch ($algorithm) {
            case 'round_robin':
                return $this->roundRobinSelection($managers, $queueId);
                
            case 'load_balanced':
                return $this->loadBalancedSelection($managers);
                
            case 'priority_based':
                return $this->priorityBasedSelection($managers, $orderData);
                
            default:
                $this->logger->warning("Неизвестный алгоритм: {$algorithm}, используем round robin");
                return $this->roundRobinSelection($managers, $queueId);
        }
    }

    /**
     * Round Robin - выбор менеджера по кругу
     */
    private function roundRobinSelection($managers, $queueId)
    {
        // Получаем текущую позицию в очереди
        $queue = $this->queue->getQueueById($queueId);
        $currentPosition = $queue['current_position'] ?? 0;
        
        // Если позиция вне диапазона, сбрасываем на 0
        if ($currentPosition >= count($managers)) {
            $currentPosition = 0;
        }
        
        // Выбираем менеджера на текущей позиции
        $selectedManager = $managers[$currentPosition];
        
        // Возвращаем выбранного менеджера
        return $selectedManager;
    }

    /**
     * Load Balanced - выбор наименее загруженного менеджера
     */
    private function loadBalancedSelection($managers)
    {
        // Получаем текущую нагрузку для каждого менеджера
        foreach ($managers as &$manager) {
            $manager['active_orders'] = $this->orderTracker->getActiveOrdersCount($manager['id']);
        }
        
        // Сортируем менеджеров по возрастанию нагрузки
        usort($managers, function($a, $b) {
            return $a['active_orders'] - $b['active_orders'];
        });
        
        // Возвращаем менеджера с наименьшей нагрузкой
        return $managers[0];
    }

    /**
     * Priority Based - выбор менеджера на основе приоритетов
     */
    private function priorityBasedSelection($managers, $orderData)
    {
        // Если у заказа есть категория, учитываем специализацию менеджера
        if (isset($orderData['category'])) {
            $category = $orderData['category'];
            
            // Фильтруем менеджеров, имеющих специализацию в этой категории
            $specializedManagers = array_filter($managers, function($manager) use ($category) {
                $specializations = json_decode($manager['specializations'] ?? '[]', true);
                return in_array($category, $specializations);
            });
            
            // Если нашли специализированных менеджеров, используем только их
            if (!empty($specializedManagers)) {
                $managers = array_values($specializedManagers);
            }
        }
        
        // Вычисляем общий вес приоритетов
        $totalWeight = array_sum(array_column($managers, 'priority'));
        
        // Выбираем случайного менеджера с учетом весов
        $random = mt_rand(1, $totalWeight);
        $current = 0;
        
        foreach ($managers as $manager) {
            $current += $manager['priority'];
            if ($random <= $current) {
                return $manager;
            }
        }
        
        // На всякий случай, если что-то пошло не так
        return $managers[0];
    }

    /**
     * Обновление статуса заказа
     */
    public function updateOrderStatus($orderId, $status, $source, $additionalData = [])
    {
        $this->logger->info("Обновление статуса заказа {$orderId} на {$status}");
        
        // Получаем информацию о заказе
        $order = $this->orderTracker->getOrderById($orderId);
        
        if (!$order) {
            $this->logger->warning("Заказ {$orderId} не найден при обновлении статуса");
            return [
                'success' => false,
                'message' => "Order {$orderId} not found"
            ];
        }
        
        // Обновляем статус заказа
        $comment = "Status updated from {$source}: " . json_encode($additionalData);
        $result = $this->orderTracker->updateOrderStatus($orderId, $status, $comment);
        
        if (!$result) {
            $this->logger->error("Ошибка при обновлении статуса заказа {$orderId}");
            return [
                'success' => false,
                'message' => "Failed to update order status"
            ];
        }
        
        return [
            'success' => true,
            'order_id' => $orderId,
            'status' => $status
        ];
    }
}