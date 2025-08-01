<?php

/**
 * Сервис для работы с API Битрикс24
 * 
 * @version 2.0
 * @updated 2025-07-31
 * @author Dagiron99
 */
class Bitrix24Service
{
    private $portalUrl;
    private $webhook;
    private $responsibleId;
    private $settings;
    private $logFile;

    /**
     * Инициализация сервиса Битрикс24
     */
    public function __construct()
    {
        $this->settings = new Settings();
        $bitrixSettings = $this->settings->getSettings('bitrix24');
        
        $this->portalUrl = $bitrixSettings['portal_url'] ?? '';
        $this->webhook = $bitrixSettings['webhook'] ?? '';
        $this->responsibleId = $bitrixSettings['responsible_id'] ?? '';
        
        // Настройка логирования
        $this->ensureLogDirectory();
        $this->logFile = BASE_PATH . '/storage/logs/bitrix24_sync.log';
        
        // Логируем инициализацию сервиса
        $this->log("Bitrix24 service initialized. Portal URL: {$this->portalUrl}");
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
        if (empty($this->portalUrl) || empty($this->webhook)) {
            return [
                'success' => false,
                'message' => 'URL портала или ключ вебхука не настроены'
            ];
        }

        try {
            // Делаем тестовый запрос к API - получаем информацию о портале
            $response = $this->makeRequest('app.info');

            if (!$response || isset($response['error'])) {
                $errorMsg = isset($response['error_description']) ? $response['error_description'] : 'Неизвестная ошибка';
                return [
                    'success' => false,
                    'message' => "Ошибка соединения с Битрикс24: {$errorMsg}"
                ];
            }

            // Также получаем имя портала
            $portalInfo = $this->makeRequest('portal.get');
            $portalName = '';
            
            if ($portalInfo && isset($portalInfo['result'])) {
                $portalName = $portalInfo['result']['NAME'] ?? '';
            }

            return [
                'success' => true,
                'portal_name' => $portalName ?: 'Битрикс24',
                'version' => $response['result']['VERSION'] ?? 'Неизвестно'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => "Исключение при подключении к Битрикс24: {$e->getMessage()}"
            ];
        }
    }

    /**
     * Выполнение HTTP-запроса к Bitrix24 API
     */
    private function makeRequest($method, $params = [])
    {
        if (empty($this->portalUrl) || empty($this->webhook)) {
            $this->log("Error: Bitrix24 portal URL or webhook not configured");
            return false;
        }

        $url = rtrim($this->portalUrl, '/') . '/rest/' . $this->webhook . '/' . $method . '.json';

        $this->log("Making request to: $url");

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
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ],
        ]);

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

        if (isset($decodedResponse['error'])) {
            $errorMessage = isset($decodedResponse['error_description']) ? $decodedResponse['error_description'] : 'Unknown error';
            $this->log("API Error: $errorMessage");
            return false;
        }

        return $decodedResponse;
    }
    
    /**
     * Создание лида в Битрикс24
     */
    public function createLead($leadData)
    {
        $this->log("Creating new lead in Bitrix24");
        
        // Настраиваем ответственного по умолчанию из настроек
        if (!isset($leadData['ASSIGNED_BY_ID']) && !empty($this->responsibleId)) {
            $leadData['ASSIGNED_BY_ID'] = $this->responsibleId;
        }
        
        // Настраиваем источник из настроек
        if (!isset($leadData['SOURCE_ID']) && isset($this->settings['order_source'])) {
            $leadData['SOURCE_ID'] = $this->settings['order_source'];
        }

        $response = $this->makeRequest('crm.lead.add', ['fields' => $leadData]);

        if (!$response || !isset($response['result'])) {
            $errorMsg = isset($response['error_description']) ? $response['error_description'] : 'Unknown error';
            $this->log("Failed to create lead in Bitrix24: $errorMsg");
            return false;
        }

        $leadId = $response['result'];
        $this->log("Successfully created lead in Bitrix24 with ID: $leadId");
        
        return [
            'success' => true,
            'id' => $leadId
        ];
    }
    
    /**
     * Создание заказа (сделки) в Битрикс24
     */
    public function createDeal($dealData)
    {
        $this->log("Creating new deal in Bitrix24");
        
        // Настраиваем ответственного по умолчанию из настроек
        if (!isset($dealData['ASSIGNED_BY_ID']) && !empty($this->responsibleId)) {
            $dealData['ASSIGNED_BY_ID'] = $this->responsibleId;
        }
        
        // Настраиваем источник из настроек
        if (!isset($dealData['SOURCE_ID']) && isset($this->settings['order_source'])) {
            $dealData['SOURCE_ID'] = $this->settings['order_source'];
        }

        $response = $this->makeRequest('crm.deal.add', ['fields' => $dealData]);

        if (!$response || !isset($response['result'])) {
            $errorMsg = isset($response['error_description']) ? $response['error_description'] : 'Unknown error';
            $this->log("Failed to create deal in Bitrix24: $errorMsg");
            return false;
        }

        $dealId = $response['result'];
        $this->log("Successfully created deal in Bitrix24 with ID: $dealId");
        
        return [
            'success' => true,
            'id' => $dealId
        ];
    }

    /**
     * Обновление статуса сделки в Битрикс24
     */
    public function updateDealStatus($dealId, $status)
    {
        $this->log("Updating deal status for ID: $dealId to: $status");
        
        // Проверяем соответствие статусов
        if (isset($this->settings['statuses']) && is_array($this->settings['statuses'])) {
            // Находим соответствующий статус в Битрикс24 на основе внутреннего статуса
            foreach ($this->settings['statuses'] as $internalStatus => $bitrixStatus) {
                if ($internalStatus === $status) {
                    $status = $bitrixStatus;
                    break;
                }
            }
        }
        
        $fields = [
            'STAGE_ID' => $status
        ];

        $response = $this->makeRequest('crm.deal.update', [
            'id' => $dealId,
            'fields' => $fields
        ]);

        if (!$response || !isset($response['result']) || $response['result'] !== true) {
            $errorMsg = isset($response['error_description']) ? $response['error_description'] : 'Unknown error';
            $this->log("Failed to update deal status in Bitrix24: $errorMsg");
            return false;
        }

        $this->log("Successfully updated deal status in Bitrix24");
        return true;
    }

    /**
     * Получение списка пользователей Bitrix24
     */
    public function getUsers()
    {
        $this->log("Getting list of users from Bitrix24");

        $response = $this->makeRequest('user.get');

        if (!$response || !isset($response['result'])) {
            $this->log("Failed to get users list");
            return [];
        }

        $users = [];
        foreach ($response['result'] as $user) {
            if (isset($user['ID'])) {
                $name = '';
                if (isset($user['NAME'])) {
                    $name .= $user['NAME'];
                }
                if (isset($user['LAST_NAME'])) {
                    if (!empty($name)) {
                        $name .= ' ';
                    }
                    $name .= $user['LAST_NAME'];
                }

                if (empty($name) && isset($user['EMAIL'])) {
                    $name = $user['EMAIL'];
                }

                $users[] = [
                    'id' => $user['ID'],
                    'name' => $name,
                    'email' => $user['EMAIL'] ?? '',
                    'active' => $user['ACTIVE'] === 'Y'
                ];
            }
        }

        $this->log("Successfully retrieved " . count($users) . " users");
        return $users;
    }

    /**
     * Получение статусов пользователей Bitrix24
     */
    public function getUserStatuses()
    {
        $this->log("Getting user statuses from Bitrix24");

        $response = $this->makeRequest('im.status.get');

        if (!$response || !isset($response['result'])) {
            $this->log("Failed to get user statuses");
            return [];
        }

        $userStatuses = [];
        foreach ($response['result'] as $userId => $status) {
            $userStatuses[] = [
                'id' => $userId,
                'status' => $status['STATUS'] ?? 'offline',
                'idle' => $status['IDLE'] ?? false,
                'mobile' => $status['MOBILE'] ?? false
            ];
        }

        $this->log("Successfully retrieved " . count($userStatuses) . " user statuses");
        return $userStatuses;
    }

    /**
     * Синхронизация менеджеров из Битрикс24 с локальной базой
     */
    public function syncManagers()
    {
        $this->log("Starting manager synchronization with Bitrix24");
        
        // Проверяем, включена ли синхронизация из Битрикс24
        if (!isset($this->settings['sync_from']) || $this->settings['sync_from'] != 1) {
            $this->log("Synchronization from Bitrix24 is disabled in settings");
            return false;
        }

        $bitrixUsers = $this->getUsers();

        if (empty($bitrixUsers)) {
            $this->log("No users found in Bitrix24 or failed to retrieve");
            return false;
        }

        // Здесь должен быть код для синхронизации менеджеров с базой данных
        $managerModel = new Manager();
        $syncCount = 0;
        
        foreach ($bitrixUsers as $bitrixUser) {
            // Проверяем, существует ли менеджер в нашей системе
            $existingManager = $managerModel->getManagerByExternalId('bitrix24', $bitrixUser['id']);
            
            if ($existingManager) {
                // Обновляем существующего менеджера
                $updateData = [
                    'name' => $bitrixUser['name'],
                    'email' => $bitrixUser['email'],
                    'is_active' => $bitrixUser['active'] ? 1 : 0,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $managerModel->updateManager($existingManager['id'], $updateData);
                $syncCount++;
            } else {
                // Создаем нового менеджера
                $newManager = [
                    'name' => $bitrixUser['name'],
                    'email' => $bitrixUser['email'],
                    'is_active' => $bitrixUser['active'] ? 1 : 0,
                    'external_id' => $bitrixUser['id'],
                    'external_system' => 'bitrix24',
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
     * Получение новых сделок из Битрикс24
     */
    public function getNewDeals($limit = 50)
    {
        $this->log("Getting new deals from Bitrix24");
        
        // Проверяем, включена ли синхронизация из Битрикс24
        if (!isset($this->settings['sync_from']) || $this->settings['sync_from'] != 1) {
            $this->log("Synchronization from Bitrix24 is disabled in settings");
            return [];
        }
        
        // Получаем время последней синхронизации или используем текущее время минус 1 день
        $lastSyncTime = $this->settings['last_sync_time'] ?? date('Y-m-d H:i:s', strtotime('-1 day'));
        
        $filter = [
            '>=DATE_CREATE' => $lastSyncTime
        ];
        
        $response = $this->makeRequest('crm.deal.list', [
            'filter' => $filter,
            'select' => ['*', 'UF_*'],
            'limit' => $limit
        ]);
        
        if (!$response || !isset($response['result'])) {
            $this->log("Failed to get new deals from Bitrix24");
            return [];
        }
        
        $this->log("Successfully retrieved " . count($response['result']) . " new deals");
        
        // Обновляем время последней синхронизации
        $integrationModel = new IntegrationModel();
        $this->settings['last_sync_time'] = date('Y-m-d H:i:s');
        $integrationModel->saveSettings('bitrix24', $this->settings);
        
        return $response['result'];
    }
}