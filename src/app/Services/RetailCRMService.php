<?php

/**
 * Сервис для работы с API RetailCRM
 * 
 * @version 2.0
 * @updated 2025-07-31
 * @author Dagiron99
 */
class RetailCRMService
{
    private $apiUrl;
    private $apiKey;
    private $site;
    private $settings;
    private $logFile;

    /**
     * Инициализация сервиса RetailCRM
     */
// В начале класса RetailCRMService:
public function __construct()
{
    $this->settings = new Settings();
    $retailCrmSettings = $this->settings->getSettings('retailcrm');
    
    $this->apiUrl = $retailCrmSettings['url'] ?? '';
    $this->apiKey = $retailCrmSettings['api_key'] ?? '';
    $this->site = $retailCrmSettings['site'] ?? '';
    
    // Настройка логирования
    $this->ensureLogDirectory();
    $this->logFile = BASE_PATH . '/storage/logs/retailcrm_api.log';
    
    // Логируем инициализацию сервиса
    $this->log("RetailCRM service initialized. URL: {$this->apiUrl}, Site: {$this->site}");
}

    /**
     * Создание директории для логов, если не существует
     */
    private function ensureLogDirectory()
    {
        $logDir = BASE_PATH . '/storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
    }

    /**
     * Логирование действий API
     */
    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $user = $_SESSION['user_login'] ?? 'System';
        $logMessage = "[$timestamp] [$user] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Проверка настроек соединения
     */
    public function testConnection()
    {
        if (empty($this->apiUrl) || empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => 'API URL или API ключ не настроены'
            ];
        }

        try {
            // Делаем тестовый запрос к API - получаем информацию о магазине
            $response = $this->makeRequest('GET', '/credentials');

            if (!$response || isset($response['errorMsg'])) {
                $errorMsg = isset($response['errorMsg']) ? $response['errorMsg'] : 'Неизвестная ошибка';
                return [
                    'success' => false,
                    'message' => "Ошибка соединения с RetailCRM: {$errorMsg}"
                ];
            }

            return [
                'success' => true,
                'crm_name' => $response['credentials']['siteName'] ?? 'RetailCRM',
                'version' => $response['credentials']['apiVersion'] ?? 'Неизвестно'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => "Исключение при подключении к RetailCRM: {$e->getMessage()}"
            ];
        }
    }

    /**
     * Выполнение HTTP-запроса к API
     */
    private function makeRequest($method, $endpoint, $params = [])
    {
        if (empty($this->apiUrl) || empty($this->apiKey)) {
            $this->log("Error: API URL or API key not configured");
            return false;
        }

        // Добавляем API ключ к параметрам
        $params['apiKey'] = $this->apiKey;
        
        // Добавляем код сайта, если указан
        if (!empty($this->site) && !isset($params['site'])) {
            $params['site'] = $this->site;
        }

        $url = rtrim($this->apiUrl, '/') . '/api/v5/' . ltrim($endpoint, '/');

        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $this->log("Making $method request to: $url");

        // Инициализация cURL
        $curl = curl_init();

        // Настройка параметров запроса
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
        ]);

        // Если это POST, PUT, PATCH запрос, добавляем данные
        if ($method !== 'GET' && !empty($params)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
            ]);
        }

        // Выполнение запроса
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($err) {
            $this->log("cURL Error: $err");
            return false;
        }

        $this->log("Response HTTP code: $httpCode");

        // Декодируем JSON-ответ
        $decodedResponse = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log("JSON decode error: " . json_last_error_msg());
            return false;
        }

        if ($httpCode >= 400) {
            $errorMessage = isset($decodedResponse['errorMsg']) ? $decodedResponse['errorMsg'] : 'Unknown error';
            $this->log("API Error: $errorMessage");
            return false;
        }

        return $decodedResponse;
    }

    /**
     * Создание заказа в RetailCRM
     */
    public function createOrder($orderData)
    {
        $this->log("Creating new order in RetailCRM");
        
        // Настраиваем параметры заказа на основе настроек интеграции
        if (!isset($orderData['order']['orderType']) && isset($this->settings['order_type'])) {
            $orderData['order']['orderType'] = $this->settings['order_type'];
        }
        
        if (!isset($orderData['order']['delivery']['code']) && isset($this->settings['delivery_type'])) {
            $orderData['order']['delivery'] = [
                'code' => $this->settings['delivery_type']
            ];
        }
        
        if (!isset($orderData['order']['payments']) && isset($this->settings['payment_type'])) {
            $orderData['order']['payments'] = [
                [
                    'type' => $this->settings['payment_type']
                ]
            ];
        }

        $response = $this->makeRequest('POST', '/orders/create', $orderData);

        if (!$response || !isset($response['success']) || $response['success'] !== true) {
            $errorMsg = isset($response['errorMsg']) ? $response['errorMsg'] : 'Unknown error';
            $this->log("Failed to create order in RetailCRM: $errorMsg");
            return false;
        }

        $orderId = $response['id'] ?? null;
        $this->log("Successfully created order in RetailCRM with ID: $orderId");
        
        return [
            'success' => true,
            'id' => $orderId
        ];
    }

    /**
     * Получение данных заказа
     */
    public function getOrder($orderId)
    {
        $this->log("Getting order data for ID: $orderId");

        $response = $this->makeRequest('GET', '/orders/' . $orderId);

        if (!$response || !isset($response['order'])) {
            $this->log("Failed to get order data for ID: $orderId");
            return false;
        }

        $this->log("Successfully retrieved order data for ID: $orderId");
        return $response['order'];
    }

    /**
     * Обновление статуса заказа в RetailCRM
     */
    public function updateOrderStatus($orderId, $status)
    {
        $this->log("Updating order status for ID: $orderId to: $status");
        
        // Проверяем соответствие статусов
        if (isset($this->settings['statuses']) && is_array($this->settings['statuses'])) {
            // Находим соответствующий статус в RetailCRM на основе внутреннего статуса
            foreach ($this->settings['statuses'] as $internalStatus => $retailcrmStatus) {
                if ($internalStatus === $status) {
                    $status = $retailcrmStatus;
                    break;
                }
            }
        }
        
        $data = [
            'order' => [
                'status' => $status
            ]
        ];

        $response = $this->makeRequest('POST', '/orders/' . $orderId . '/edit', $data);

        if (!$response || !isset($response['success']) || $response['success'] !== true) {
            $errorMsg = isset($response['errorMsg']) ? $response['errorMsg'] : 'Unknown error';
            $this->log("Failed to update order status in RetailCRM: $errorMsg");
            return false;
        }

        $this->log("Successfully updated order status in RetailCRM");
        return true;
    }

    /**
     * Получение списка менеджеров из RetailCRM
     */
    public function getManagers()
    {
        $this->log("Getting list of managers from RetailCRM");

        $response = $this->makeRequest('GET', '/users');

        if (!$response || !isset($response['users'])) {
            $this->log("Failed to get managers list");
            return [];
        }

        $managers = [];
        foreach ($response['users'] as $user) {
            if (isset($user['id']) && isset($user['firstName'])) {
                $fullName = $user['firstName'];
                if (isset($user['lastName'])) {
                    $fullName .= ' ' . $user['lastName'];
                }

                $managers[] = [
                    'id' => $user['id'],
                    'name' => $fullName,
                    'email' => $user['email'] ?? '',
                    'active' => $user['active'] ?? false
                ];
            }
        }

        $this->log("Successfully retrieved " . count($managers) . " managers");
        return $managers;
    }

    /**
     * Синхронизация менеджеров из RetailCRM с локальной базой
     */
    public function syncManagers()
    {
        $this->log("Starting manager synchronization with RetailCRM");
        
        // Проверяем, включена ли синхронизация из RetailCRM
        if (!isset($this->settings['sync_from']) || $this->settings['sync_from'] != 1) {
            $this->log("Synchronization from RetailCRM is disabled in settings");
            return false;
        }

        $retailcrmManagers = $this->getManagers();

        if (empty($retailcrmManagers)) {
            $this->log("No managers found in RetailCRM or failed to retrieve");
            return false;
        }

        // Здесь должен быть код для синхронизации менеджеров с базой данных
        $managerModel = new Manager();
        $syncCount = 0;
        
        foreach ($retailcrmManagers as $retailcrmManager) {
            // Проверяем, существует ли менеджер в нашей системе
            $existingManager = $managerModel->getManagerByExternalId('retailcrm', $retailcrmManager['id']);
            
            if ($existingManager) {
                // Обновляем существующего менеджера
                $updateData = [
                    'name' => $retailcrmManager['name'],
                    'email' => $retailcrmManager['email'],
                    'is_active' => $retailcrmManager['active'] ? 1 : 0,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $managerModel->updateManager($existingManager['id'], $updateData);
                $syncCount++;
            } else {
                // Создаем нового менеджера
                $newManager = [
                    'name' => $retailcrmManager['name'],
                    'email' => $retailcrmManager['email'],
                    'is_active' => $retailcrmManager['active'] ? 1 : 0,
                    'external_id' => $retailcrmManager['id'],
                    'external_system' => 'retailcrm',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $managerModel->createManager($newManager);
                $syncCount++;
            }
        }

        $this->log("Synchronization completed successfully. Updated $syncCount managers.");
        return true;
    }
    
    /**
     * Получение списка новых заказов из RetailCRM
     */
    public function getNewOrders($limit = 50)
    {
        $this->log("Getting new orders from RetailCRM");
        
        // Проверяем, включена ли синхронизация из RetailCRM
        if (!isset($this->settings['sync_from']) || $this->settings['sync_from'] != 1) {
            $this->log("Synchronization from RetailCRM is disabled in settings");
            return [];
        }
        
        // Получаем время последней синхронизации или используем текущее время минус 1 день
        $lastSyncTime = $this->settings['last_sync_time'] ?? date('Y-m-d H:i:s', strtotime('-1 day'));
        
        $params = [
            'filter' => [
                'startDate' => $lastSyncTime,
                'endDate' => date('Y-m-d H:i:s')
            ],
            'limit' => $limit
        ];
        
        $response = $this->makeRequest('GET', '/orders', $params);
        
        if (!$response || !isset($response['orders'])) {
            $this->log("Failed to get new orders from RetailCRM");
            return [];
        }
        
        $this->log("Successfully retrieved " . count($response['orders']) . " new orders");
        
        // Обновляем время последней синхронизации
        $integrationModel = new IntegrationModel();
        $this->settings['last_sync_time'] = date('Y-m-d H:i:s');
        $integrationModel->saveSettings('retailcrm', $this->settings);
        
        return $response['orders'];
    }
}