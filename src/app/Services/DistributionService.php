<?php


class DistributionService
{
    private $distributor;
    private $orderTracker;
    private $logger;
    private $settings;

    public function __construct()
    {
        $this->distributor = new Distributor();
        $this->orderTracker = new OrderTracker();
        $this->logger = new Logger('distribution_service.log');
        $this->settings = new Settings();
    }

    /**
     * Распределение заказа из внешней системы
     */
    public function processOrder($orderData, $source)
    {
        $this->logger->info("Processing order from $source: " . json_encode($orderData));

        // Проверяем, что необходимые данные заказа присутствуют
        if (!isset($orderData['order_id'])) {
            $this->logger->error("Order ID is missing");
            return [
                'success' => false,
                'message' => 'Order ID is required'
            ];
        }

        // Проверяем, существует ли уже этот заказ в системе
        $existingOrder = $this->orderTracker->getOrderById($orderData['order_id']);
        if ($existingOrder) {
            $this->logger->warning("Order {$orderData['order_id']} already exists in the system");
            return [
                'success' => false,
                'message' => 'Order already exists',
                'order_id' => $orderData['order_id'],
                'manager' => [
                    'id' => $existingOrder['manager_id'],
                    'name' => $existingOrder['manager_name']
                ]
            ];
        }

        // Определяем очередь для распределения
        $queueId = $this->determineQueue($orderData, $source);

        if (!$queueId) {
            $this->logger->error("Could not determine queue for order {$orderData['order_id']}");
            return [
                'success' => false,
                'message' => 'Could not determine queue for this order'
            ];
        }

        // Добавляем информацию о платформе
        $orderData['platform'] = $source;

        // Распределяем заказ
        return $this->distributor->distributeOrder($orderData, $queueId);
    }

    /**
     * Определение очереди для заказа
     */
    private function determineQueue($orderData, $source)
    {
        // Получаем настройки маппинга очередей
        $queueMappings = json_decode($this->settings->get('queue_mappings', '{}'), true);

        // Проверяем наличие маппинга для источника
        if (isset($queueMappings[$source])) {
            $sourceMapping = $queueMappings[$source];

            // Проходим по правилам маппинга
            foreach ($sourceMapping as $rule) {
                if ($this->matchesRule($orderData, $rule['conditions'])) {
                    $this->logger->info("Order {$orderData['order_id']} matched rule for queue {$rule['queue_id']}");
                    return $rule['queue_id'];
                }
            }
        }

        // Если подходящее правило не найдено, используем очередь по умолчанию
        $defaultQueueId = $this->settings->get('default_queue_id');

        if ($defaultQueueId) {
            $this->logger->info("Using default queue $defaultQueueId for order {$orderData['order_id']}");
            return $defaultQueueId;
        }

        $this->logger->error("No queue found for order {$orderData['order_id']}");
        return null;
    }

    /**
     * Проверка соответствия заказа условиям правила
     */
    private function matchesRule($orderData, $conditions)
    {
        if (empty($conditions)) {
            return true; // Если условий нет, правило подходит
        }

        foreach ($conditions as $condition) {
            $field = $condition['field'];
            $operator = $condition['operator'];
            $value = $condition['value'];

            // Получаем значение поля из данных заказа
            $fieldValue = $this->getFieldValue($orderData, $field);

            // Проверяем условие
            if (!$this->checkCondition($fieldValue, $operator, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Получение значения поля из заказа
     */
    private function getFieldValue($orderData, $field)
    {
        // Поддержка вложенных полей через точечную нотацию
        $keys = explode('.', $field);
        $value = $orderData;

        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Проверка условия
     */
    private function checkCondition($fieldValue, $operator, $expectedValue)
    {
        switch ($operator) {
            case 'equals':
                return $fieldValue == $expectedValue;
            case 'not_equals':
                return $fieldValue != $expectedValue;
            case 'contains':
                return is_string($fieldValue) && strpos($fieldValue, $expectedValue) !== false;
            case 'starts_with':
                return is_string($fieldValue) && strpos($fieldValue, $expectedValue) === 0;
            case 'ends_with':
                return is_string($fieldValue) && substr($fieldValue, -strlen($expectedValue)) === $expectedValue;
            case 'greater_than':
                return $fieldValue > $expectedValue;
            case 'less_than':
                return $fieldValue < $expectedValue;
            case 'in':
                return in_array($fieldValue, is_array($expectedValue) ? $expectedValue : [$expectedValue]);
            case 'not_in':
                return !in_array($fieldValue, is_array($expectedValue) ? $expectedValue : [$expectedValue]);
            default:
                return false;
        }
    }

    /**
     * Обновление статуса заказа
     */
    public function updateOrderStatus($orderId, $status, $source, $additionalData = [])
    {
        $this->logger->info("Updating status for order $orderId to '$status' from $source");

        // Проверяем существование заказа
        $order = $this->orderTracker->getOrderById($orderId);

        if (!$order) {
            $this->logger->error("Order $orderId not found");
            return [
                'success' => false,
                'message' => 'Order not found'
            ];
        }

        // Обновляем статус заказа
        $platformData = [
            'source' => $source,
            'timestamp' => date('Y-m-d H:i:s'),
            'additional' => $additionalData
        ];

        $result = $this->orderTracker->updateOrderStatus($orderId, $status, $platformData);

        if ($result) {
            $this->logger->info("Status for order $orderId updated successfully");

            // Если заказ завершен, уменьшаем нагрузку менеджера
            if (in_array($status, ['completed', 'cancelled', 'error'])) {
                $queueModel = new Queue();
                $queueModel->decrementManagerLoad($order['queue_id'], $order['manager_id']);
                $this->logger->info("Decreased load for manager #{$order['manager_id']} in queue #{$order['queue_id']}");
            }

            return [
                'success' => true,
                'message' => 'Order status updated successfully',
                'order_id' => $orderId,
                'status' => $status
            ];
        } else {
            $this->logger->error("Failed to update status for order $orderId");
            return [
                'success' => false,
                'message' => 'Failed to update order status'
            ];
        }
    }
}