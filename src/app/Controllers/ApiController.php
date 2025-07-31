<?php

namespace App\Controllers;

use App\Services\DistributionService;
use App\Services\Logger;
use App\Models\Queue;
use App\Services\ValidationService;
use App\Services\QueueService;

class ApiController extends BaseController
{
    private $distributionService;
    private $logger;
    private $validationService;
    private $queueService;

    public function __construct()
    {
        $this->distributionService = new DistributionService();
        $this->logger = new Logger('api.log');
        $this->validationService = new ValidationService();
        $this->queueService = new QueueService();
    }

    /**
     * Отправка JSON-ответа
     */
    private function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * API для распределения заказа
     */
    public function distributeOrder()
    {
        // Проверяем метод запроса
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json([
                'success' => false,
                'message' => 'Method not allowed'
            ], 405);
        }

        // Получаем данные запроса
        $input = file_get_contents('php://input');

        if (empty($input)) {
            $this->logger->error('Empty request data');
            return $this->json([
                'success' => false,
                'message' => 'Empty request data'
            ], 400);
        }

        // Пытаемся декодировать JSON
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Invalid JSON: ' . json_last_error_msg());
            return $this->json([
                'success' => false,
                'message' => 'Invalid JSON: ' . json_last_error_msg()
            ], 400);
        }

        // Проверяем обязательные поля
        if (!isset($data['order_id'])) {
            $this->logger->error('Missing required field: order_id');
            return $this->json([
                'success' => false,
                'message' => 'Missing required field: order_id'
            ], 400);
        }

        // Логируем запрос
        $this->logger->info('Distribute order request: ' . json_encode($data));

        // Обрабатываем запрос через сервис
        $result = $this->distributionService->processOrder($data, 'api');

        // Логируем результат
        $logMessage = $result['success']
            ? 'Order distributed successfully: ' . $data['order_id']
            : 'Order distribution failed: ' . ($result['message'] ?? 'Unknown error');

        $this->logger->info($logMessage);

        $result = [
            'success' => false,
            'message' => 'No operation was performed for the given event'
        ];
        return $this->json($result);
    }

    /**
     * API для получения данных менеджера
     */
    public function getManager()
    {
        // Проверка API-ключа
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        if (!$this->validateApiKey($apiKey)) {
            $this->jsonResponse(['error' => 'Недействительный API-ключ'], 401);
            return;
        }

        // Валидация входящих данных
        $rules = [
            'line_id' => 'required|integer',
            'order_id' => 'required|integer'
        ];

        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        if (!$this->validationService->validate($data, $rules)) {
            $this->jsonResponse([
                'error' => 'Ошибка валидации',
                'details' => $this->validationService->getErrors()
            ], 400);
            return;
        }

        // Получение менеджера
        $result = $this->queueService->getNextManager($data['line_id'], $data['order_id']);

        if ($result['success']) {
            $this->jsonResponse(['manager_id' => $result['manager_id']]);
        } else {
            $this->jsonResponse(['error' => $result['message']], 500);
        }
    }

    private function validateApiKey($apiKey)
    {
        // Проверка API-ключа из конфигурации или базы данных
        $validApiKey = getenv('API_KEY') ?: 'default_api_key'; // Временное решение
        return hash_equals($validApiKey, $apiKey);
    }

    /**
     * API для получения статуса очереди
     */
    public function getQueueStatus($id)
    {
        // Проверяем, что ID передан
        if (!$id) {
            return $this->json([
                'success' => false,
                'message' => 'Queue ID is required'
            ], 400);
        }

        // Получаем данные очереди
        $queueModel = new Queue();
        $queue = $queueModel->getQueueById($id);

        if (!$queue) {
            return $this->json([
                'success' => false,
                'message' => 'Queue not found'
            ], 404);
        }

        // Получаем активных менеджеров для очереди
        $managers = $queueModel->getActiveManagersForQueue($id);

        // Получаем статистику распределения
        $stats = $queueModel->getQueueDistributionStats($id);

        return $this->json([
            'success' => true,
            'queue' => [
                'id' => $queue['id'],
                'name' => $queue['name'],
                'algorithm' => $queue['algorithm'],
                'current_position' => $queue['current_position'],
                'is_active' => (bool) $queue['is_active'],
                'active_managers' => count($managers),
                'total_orders' => $stats['total_orders'],
                'successful_distributions' => $stats['successful_distributions'],
                'failed_distributions' => $stats['failed_distributions']
            ]
        ]);
    }

    /**
     * Обработка webhook от Bitrix24
     */
    public function processBitrix24Webhook()
    {
        $this->logger->info('Received Bitrix24 webhook');

        // Получаем данные запроса
        $input = file_get_contents('php://input');

        if (empty($input)) {
            $this->logger->error('Empty webhook data');
            return $this->json([
                'success' => false,
                'message' => 'Empty webhook data'
            ], 400);
        }

        // Пытаемся декодировать JSON
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Invalid JSON in webhook: ' . json_last_error_msg());
            return $this->json([
                'success' => false,
                'message' => 'Invalid JSON: ' . json_last_error_msg()
            ], 400);
        }

        // Логируем полученные данные
        $this->logger->info('Webhook data: ' . json_encode($data));

        // Определяем тип события
        $event = $data['event'] ?? '';

        switch ($event) {
            case 'ONCRMDEALADD':
                return $this->handleDealAdd($data);

            case 'ONCRMDEALUPDATE':
                return $this->handleDealUpdate($data);

            default:
                $this->logger->warning('Unsupported event type: ' . $event);
                return $this->json([
                    'success' => false,
                    'message' => 'Unsupported event type: ' . $event
                ]);
        }
    }

    /**
     * Обработка события создания сделки
     */
    private function handleDealAdd($data)
    {
        // Извлекаем ID сделки
        $dealId = $data['data']['FIELDS']['ID'] ?? null;

        if (!$dealId) {
            $this->logger->error('Deal ID not found in webhook data');
            return $this->json([
                'success' => false,
                'message' => 'Deal ID not found'
            ]);
        }

        // Формируем данные заказа
        $orderData = [
            'order_id' => 'bitrix24_' . $dealId,
            'title' => $data['data']['FIELDS']['TITLE'] ?? 'Сделка #' . $dealId,
            'price' => $data['data']['FIELDS']['OPPORTUNITY'] ?? 0,
            'currency' => $data['data']['FIELDS']['CURRENCY_ID'] ?? 'RUB',
            'status' => $data['data']['FIELDS']['STAGE_ID'] ?? 'NEW',
            'source' => 'bitrix24',
            'raw_data' => $data['data']['FIELDS']
        ];

        // Передаем данные в сервис распределения
        $result = $this->distributionService->processOrder($orderData, 'bitrix24');

        // Логируем результат
        $logMessage = $result['success']
            ? 'Deal successfully distributed: ' . $dealId
            : 'Deal distribution failed: ' . ($result['message'] ?? 'Unknown error');

        $this->logger->info($logMessage);

        return $this->json($result);
    }

    /**
     * Обработка события обновления сделки
     */
    private function handleDealUpdate($data)
    {
        // Извлекаем ID сделки
        $dealId = $data['data']['FIELDS']['ID'] ?? null;

        if (!$dealId) {
            $this->logger->error('Deal ID not found in webhook data');
            return $this->json([
                'success' => false,
                'message' => 'Deal ID not found'
            ]);
        }

        // Формируем orderId в том же формате, что и при создании
        $orderId = 'bitrix24_' . $dealId;

        // Определяем новый статус
        $status = $data['data']['FIELDS']['STAGE_ID'] ?? null;

        if (!$status) {
            $this->logger->warning('Status not found in deal update');
            return $this->json([
                'success' => false,
                'message' => 'Status not found in deal update'
            ]);
        }

        // Маппим статусы Bitrix24 на наши внутренние статусы
        $statusMapping = [
            'NEW' => 'new',
            'PREPARATION' => 'processing',
            'PREPAYMENT_INVOICE' => 'processing',
            'EXECUTING' => 'processing',
            'FINAL_INVOICE' => 'processing',
            'WON' => 'completed',
            'LOSE' => 'cancelled'
        ];

        $mappedStatus = $statusMapping[$status] ?? 'processing';

        // Обновляем статус заказа
        $result = $this->distributionService->updateOrderStatus(
            $orderId,
            $mappedStatus,
            'bitrix24',
            [
                'deal_id' => $dealId,
                'status' => $status,
                'raw_data' => $data['data']['FIELDS']
            ]
        );

        // Логируем результат
        $logMessage = $result['success']
            ? "Deal status updated: $dealId -> $mappedStatus"
            : 'Deal status update failed: ' . ($result['message'] ?? 'Unknown error');

        $this->logger->info($logMessage);

        return $this->json($result);
    }
    /**
     * Обработка webhook от RetailCRM
     */
    public function processRetailCRMWebhook()
    {
        $this->logger->info('Received RetailCRM webhook');

        // Получаем данные запроса
        $input = file_get_contents('php://input');

        if (empty($input)) {
            $this->logger->error('Empty webhook data');
            return $this->json([
                'success' => false,
                'message' => 'Empty webhook data'
            ], 400);
        }

        // Декодируем JSON
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Invalid JSON in webhook: ' . json_last_error_msg());
            return $this->json([
                'success' => false,
                'message' => 'Invalid JSON: ' . json_last_error_msg()
            ], 400);
        }

        // Логируем данные
        $this->logger->info('RetailCRM webhook data: ' . json_encode($data));

        // Определяем тип события
        $topic = $data['topic'] ?? '';

        switch ($topic) {
            case 'orders.create':
                return $this->handleRetailCRMOrderCreate($data);

            case 'orders.status.changed':
                return $this->handleRetailCRMStatusChanged($data);

            default:
                $this->logger->warning('Unsupported RetailCRM event: ' . $topic);
                return $this->json([
                    'success' => false,
                    'message' => 'Unsupported event: ' . $topic
                ]);
        }
    }

    /**
     * Обработка создания заказа в RetailCRM
     */
    private function handleRetailCRMOrderCreate($data)
    {
        // Извлекаем данные заказа
        $order = $data['payload']['order'] ?? null;

        if (!$order || !isset($order['id'])) {
            $this->logger->error('Order data not found in webhook');
            return $this->json([
                'success' => false,
                'message' => 'Order data not found'
            ]);
        }

        // Формируем данные для обработки
        $orderData = [
            'order_id' => 'retailcrm_' . $order['id'],
            'title' => 'Заказ #' . $order['number'],
            'price' => $order['totalSumm'] ?? 0,
            'items' => $order['items'] ?? [],
            'customer' => [
                'name' => $order['firstName'] ?? '',
                'phone' => $order['phone'] ?? '',
                'email' => $order['email'] ?? ''
            ],
            'source' => 'retailcrm',
            'callback_requested' => isset($order['customFields']['callback_required']) && $order['customFields']['callback_required'] === 'yes',
            'raw_data' => $order
        ];

        // Передаем заказ в сервис распределения
        $result = $this->distributionService->processOrder($orderData, 'retailcrm');

        // Логируем результат
        $this->logger->info('RetailCRM order distribution result: ' . json_encode($result));

        return $this->json($result);
    }

    /**
     * Обработка изменения статуса заказа в RetailCRM
     */
    private function handleRetailCRMStatusChanged($data)
    {
        // Извлекаем данные
        $order = $data['payload']['order'] ?? null;

        if (!$order || !isset($order['id'])) {
            $this->logger->error('Order data not found in webhook');
            return $this->json([
                'success' => false,
                'message' => 'Order data not found'
            ]);
        }

        // Формируем ID заказа в том же формате, как при создании
        $orderId = 'retailcrm_' . $order['id'];

        // Получаем новый статус
        $retailCrmStatus = $order['status'] ?? 'new';

        // Маппинг статусов RetailCRM на внутренние
        $statusMapping = [
            'new' => 'new',
            'approval' => 'processing',
            'assembling' => 'processing',
            'ready-for-delivery' => 'processing',
            'delivering' => 'processing',
            'complete' => 'completed',
            'canceled' => 'cancelled'
        ];

        $mappedStatus = $statusMapping[$retailCrmStatus] ?? 'processing';

        // Обновляем статус
        $result = $this->distributionService->updateOrderStatus(
            $orderId,
            $mappedStatus,
            'retailcrm',
            [
                'retailcrm_status' => $retailCrmStatus,
                'order_number' => $order['number'] ?? '',
                'changed_at' => date('Y-m-d H:i:s')
            ]
        );

        return $this->json($result);
    }
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}