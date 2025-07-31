<?php

namespace App\Controllers;

use App\Models\Manager;
use App\Models\Queue;
use App\Models\OrderTracker;
use App\Services\Logger;
// Ensure the correct namespace for SimpleCache is imported
use App\Libraries\SimpleCache; // Replace with the correct namespace if different


/**
 * Контроллер панели управления системы распределения заказов
 */
class DashboardController extends BaseController
{
    private $managerModel;
    private $queueModel;
    private $orderTracker;
    private $logger;
    private $cache;
    private $cacheLifetime = 300; // 5 минут кэширования

    /**
     * Инициализация контроллера
     */
    public function __construct()
    {
        // Инициализация моделей
        $this->managerModel = new Manager();
        $this->queueModel = new Queue();
        $this->orderTracker = new OrderTracker();
        $this->logger = new Logger('dashboard');
        
        // Инициализация кэша
        $this->initCache();
    }
    
    /**
     * Инициализация системы кэширования
     */
    private function initCache()
    {
        $cacheDir = BASE_PATH . '/cache/';
        
        // Создаем директорию для кэша, если её нет
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $this->cache = new SimpleCache($cacheDir);
    }

    /**
     * Отображение панели управления
     */
    public function index()
    {
        try {
            // Получаем все данные с обработкой ошибок для каждого блока
            $activeManagers = $this->getActiveManagers();
            $inactiveManagers = $this->getInactiveManagers();
            $queues = $this->getQueues();
            $orderStats = $this->getOrderStats();
            $distributionStats = $this->getDistributionStats();
            $recentOrders = $this->getRecentOrders();
            $recentLogs = $this->getRecentLogs();
            
            // Отображаем представление
            $this->view('dashboard/index', [
                'pageTitle' => 'Панель управления',
                'activeManagers' => $activeManagers,
                'inactiveManagers' => $inactiveManagers,
                'queues' => $queues,
                'orderStats' => $orderStats,
                'distributionStats' => $distributionStats,
                'recentOrders' => $recentOrders,
                'recentLogs' => $recentLogs,
                'pageScripts' => [
                    'https://cdn.jsdelivr.net/npm/chart.js',
                    '/js/dashboard.js'
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->log("Dashboard error: " . $e->getMessage());
            
            // Отображаем ошибку
            $this->view('errors/error', [
                'pageTitle' => 'Ошибка загрузки',
                'errorMessage' => 'Произошла ошибка при загрузке панели управления.',
                'errorDetails' => isset($GLOBALS['DEBUG']) && $GLOBALS['DEBUG'] ? $e->getMessage() : null
            ]);
        }
    }
    
    /**
     * Получение активных менеджеров с кэшированием
     */
    private function getActiveManagers()
    {
        $cacheKey = 'active_managers';
        
        if ($cachedData = $this->cache->get($cacheKey)) {
            return $cachedData;
        }
        
        try {
            $managers = $this->managerModel->getActiveManagers();
            
            // Обогащаем данные о менеджерах информацией о текущей нагрузке
            foreach ($managers as &$manager) {
                if (!isset($manager['current_load'])) {
                    // Если поле current_load не заполнено моделью, рассчитываем его
                    $manager['current_load'] = $this->orderTracker->getManagerActiveOrdersCount($manager['id']);
                }
                
                // Установка max_load по умолчанию, если не задано
                if (!isset($manager['max_load'])) {
                    $manager['max_load'] = $manager['max_orders_count'] ?? 10;
                }
            }
            
            $this->cache->set($cacheKey, $managers, $this->cacheLifetime);
            return $managers;
            
        } catch (\Exception $e) {
            $this->logger->log("Error getting active managers: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получение неактивных менеджеров с кэшированием
     */
    private function getInactiveManagers()
    {
        $cacheKey = 'inactive_managers';
        
        if ($cachedData = $this->cache->get($cacheKey)) {
            return $cachedData;
        }
        
        try {
            $managers = $this->managerModel->getInactiveManagers();
            $this->cache->set($cacheKey, $managers, $this->cacheLifetime);
            return $managers;
            
        } catch (\Exception $e) {
            $this->logger->log("Error getting inactive managers: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получение очередей с кэшированием
     */
    private function getQueues()
    {
        $cacheKey = 'queues';
        
        if ($cachedData = $this->cache->get($cacheKey)) {
            return $cachedData;
        }
        
        try {
            $queues = $this->queueModel->getAllQueues();
            $this->cache->set($cacheKey, $queues, $this->cacheLifetime);
            return $queues;
            
        } catch (\Exception $e) {
            $this->logger->log("Error getting queues: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получение статистики заказов с кэшированием
     */
    private function getOrderStats()
    {
        $cacheKey = 'order_stats';
        
        if ($cachedData = $this->cache->get($cacheKey)) {
            return $cachedData;
        }
        
        try {
            // Пытаемся получить статистику из OrderTracker
            $stats = $this->orderTracker->getStatistics();
            
            // Если метод getStatistics выдает ошибку или пустой результат,
            // собираем базовую статистику вручную
            if (empty($stats)) {
                $stats = [
                    'total_orders' => 0,
                    'in_progress' => 0,
                    'processed' => 0,
                    'failed' => 0,
                    'today' => 0
                ];
            }
            
            $this->cache->set($cacheKey, $stats, $this->cacheLifetime);
            return $stats;
            
        } catch (\Exception $e) {
            $this->logger->log("Error getting order statistics: " . $e->getMessage());
            
            // Возвращаем базовую структуру с нулевыми значениями
            return [
                'total_orders' => 0,
                'in_progress' => 0,
                'processed' => 0,
                'failed' => 0,
                'today' => 0
            ];
        }
    }
    
    /**
     * Получение статистики распределения с кэшированием
     */
    private function getDistributionStats()
    {
        $cacheKey = 'distribution_stats';
        
        if ($cachedData = $this->cache->get($cacheKey)) {
            return $cachedData;
        }
        
        try {
            // Получаем статистику распределения за последние 7 дней
            $stats = $this->managerModel->getDistributionStatsByDay(7);
            
            // Если данных нет, создаем пустую статистику для последних 7 дней
            if (empty($stats)) {
                $stats = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime("-$i days"));
                    $stats[] = [
                        'date' => $date,
                        'count' => 0
                    ];
                }
            }
            
            // Убедимся, что есть данные за все 7 дней
            $existingDates = array_column($stats, 'date');
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                if (!in_array($date, $existingDates)) {
                    $stats[] = [
                        'date' => $date,
                        'count' => 0
                    ];
                }
            }
            
            // Сортируем по дате
            usort($stats, function($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
            });
            
            $this->cache->set($cacheKey, $stats, $this->cacheLifetime);
            return $stats;
            
        } catch (\Exception $e) {
            $this->logger->log("Error getting distribution stats: " . $e->getMessage());
            
            // Возвращаем пустую статистику
            $stats = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $stats[] = [
                    'date' => $date,
                    'count' => 0
                ];
            }
            return $stats;
        }
    }
    
    /**
     * Получение последних заказов
     */
    private function getRecentOrders()
    {
        $cacheKey = 'recent_orders';
        
        if ($cachedData = $this->cache->get($cacheKey)) {
            return $cachedData;
        }
        
        try {
            // Получаем последние заказы через OrderTracker
            $orders = [];
            
            // Пробуем разные методы
            if (method_exists($this->orderTracker, 'getRecentOrders')) {
                $orders = $this->orderTracker->getRecentOrders(5);
            } else {
                $orders = $this->orderTracker->getAllOrders(5, 0);
            }
            
            // Для каждого заказа добавляем имя менеджера, если его нет
            foreach ($orders as &$order) {
                if (!isset($order['manager_name']) && isset($order['manager_id'])) {
                    // Безопасно получаем имя менеджера, используя поиск в массиве
                    $manager = $this->findManagerById($order['manager_id']);
                    $order['manager_name'] = $manager ? $manager['name'] : 'ID: ' . $order['manager_id'];
                }
                
                // Для соответствия формату шаблона
                if (!isset($order['id'])) {
                    $order['id'] = $order['order_id'] ?? $order['id'] ?? 'Н/Д';
                }
                
                // Для соответствия формату шаблона
                if (!isset($order['status']) && isset($order['current_status'])) {
                    $order['status'] = $order['current_status'];
                }
                
                // Для соответствия формату шаблона
                if (!isset($order['created_at']) && isset($order['assigned_at'])) {
                    $order['created_at'] = $order['assigned_at'];
                }
            }
            
            $this->cache->set($cacheKey, $orders, $this->cacheLifetime);
            return $orders;
            
        } catch (\Exception $e) {
            $this->logger->log("Error getting recent orders: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получение последних логов системы
     */
    private function getRecentLogs()
    {
        $cacheKey = 'recent_logs';
        
        if ($cachedData = $this->cache->get($cacheKey)) {
            return $cachedData;
        }
        
        try {
            // Читаем логи из файла как запасной вариант
            $logs = [];
            $logFile = BASE_PATH . '/logs/system.log';
            
            if (file_exists($logFile)) {
                $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $lines = array_slice($lines, -10); // Последние 10 строк
                
                foreach ($lines as $line) {
                    if (preg_match('/\[(.*?)\].*?\[(.*?)\] (.*)/', $line, $matches)) {
                        $logs[] = [
                            'created_at' => $matches[1],
                            'level' => strtolower($matches[2]),
                            'message' => $matches[3]
                        ];
                    }
                }
            }
            
            $this->cache->set($cacheKey, $logs, $this->cacheLifetime);
            return $logs;
            
        } catch (\Exception $e) {
            $this->logger->log("Error getting recent logs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Поиск менеджера по ID в списке активных и неактивных менеджеров
     */
    private function findManagerById($managerId)
    {
        $activeManagers = $this->managerModel->getActiveManagers();
        $inactiveManagers = $this->managerModel->getInactiveManagers();
        
        // Ищем в активных менеджерах
        foreach ($activeManagers as $manager) {
            if ($manager['id'] == $managerId) {
                return $manager;
            }
        }
        
        // Ищем в неактивных менеджерах
        foreach ($inactiveManagers as $manager) {
            if ($manager['id'] == $managerId) {
                return $manager;
            }
        }
        
        return null;
    }
    
    /**
     * AJAX-метод для обновления статистики без перезагрузки страницы
     */
    public function ajaxUpdate()
    {
        try {
            // Сбрасываем кэш для получения актуальных данных
            $this->cache->delete('active_managers');
            $this->cache->delete('order_stats');
            $this->cache->delete('distribution_stats');
            $this->cache->delete('recent_orders');
            
            // Получаем обновленные данные
            $data = [
                'activeManagers' => $this->getActiveManagers(),
                'orderStats' => $this->getOrderStats(),
                'distributionStats' => $this->getDistributionStats(),
                'recentOrders' => $this->getRecentOrders(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            // Возвращаем JSON-ответ
            $this->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            $this->logger->log("AJAX update error: " . $e->getMessage());
            
            $this->json([
                'success' => false,
                'message' => 'Ошибка обновления данных',
                'error' => isset($GLOBALS['DEBUG']) && $GLOBALS['DEBUG'] ? $e->getMessage() : null
            ], 500);
        }
    }
}

