<?php
namespace App\Controllers;

use App\Models\OrderTracker;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Models\Manager;
use App\Models\Queue;
use App\Services\DistributionService;
use App\Services\DatabaseService; // Правильный импорт
use \PDO;
use \PDOException;

class OrderTrackingController extends BaseController
{
    private $orderTracker;
    private $logger;
    private $distributionService;
    private $db;

    public function __construct()
    {
        $this->logger = new Logger('orders_tracking');
        
        $this->orderTracker = new OrderTracker();
    
        $this->db = DatabaseService::getInstance()->getConnection();
        $this->distributionService = new DistributionService();
    }

    /**
     * Отображение страницы отслеживания заказов
     */
    public function index()
    {
        // Получаем параметры фильтрации
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $managerId = isset($_GET['manager_id']) ? (int) $_GET['manager_id'] : null;
        $queueId = isset($_GET['queue_id']) ? (int) $_GET['queue_id'] : null;
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;

        // Получаем список заказов с фильтрацией
        $orders = $this->getFilteredOrders($status, $managerId, $queueId, $dateFrom, $dateTo);

        // Получаем менеджеров и очереди для выпадающих списков фильтрации
        $managerModel = new Manager();
        $managers = $managerModel->getAllManagers();

        $queueModel = new Queue();
        $queues = $queueModel->getAllQueues();

        // Получаем статистику
        $statistics = $this->orderTracker->getStatistics();

        // Отображаем представление
        $this->view('orders_tracking/index', [
            'pageTitle' => 'Отслеживание заказов',
            'orders' => $orders,
            'managers' => $managers,
            'queues' => $queues,
            'status' => $status,
            'managerId' => $managerId,
            'queueId' => $queueId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'statistics' => $statistics,
            'pageScripts' => ['/js/orders_tracking.js']
        ]);
    }

    /**
     * Получение отфильтрованных заказов
     */
    private function getFilteredOrders($status, $managerId, $queueId, $dateFrom, $dateTo)
    {
        try {
            $conditions = [];
            $params = [];

            if ($status) {
                $conditions[] = "ot.status = :status";
                $params[':status'] = $status;
            }

            if ($managerId) {
                $conditions[] = "ot.manager_id = :manager_id";
                $params[':manager_id'] = $managerId;
            }

            if ($queueId) {
                $conditions[] = "ot.queue_id = :queue_id";
                $params[':queue_id'] = $queueId;
            }

            if ($dateFrom) {
                $conditions[] = "ot.created_at >= :date_from";
                $params[':date_from'] = $dateFrom . ' 00:00:00';
            }

            if ($dateTo) {
                $conditions[] = "ot.created_at <= :date_to";
                $params[':date_to'] = $dateTo . ' 23:59:59';
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            $db = DatabaseService::getInstance()->getConnection();
            $query = "
                SELECT 
                    ot.*,
                    m.name as manager_name,
                    q.name as queue_name
                FROM orders_tracking ot
                LEFT JOIN managers m ON ot.manager_id = m.id
                LEFT JOIN queues q ON ot.queue_id = q.id
                $whereClause
                ORDER BY ot.created_at DESC
                LIMIT 500
            ";

            $stmt = $db->prepare($query);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error("Error getting filtered orders: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Получение деталей заказа
     */
    public function getDetails()
    {
        if (!isset($_GET['order_id'])) {
            $this->json(['success' => false, 'message' => 'Order ID is required']);
            return;
        }

        $orderId = $_GET['order_id'];

        // Получаем данные заказа
        $order = $this->orderTracker->getOrderById($orderId);

        if (!$order) {
            $this->json(['success' => false, 'message' => 'Order not found']);
            return;
        }

        // Получаем историю статусов
        $statusHistory = $this->orderTracker->getOrderStatusHistory($orderId);

        // Декодируем JSON-данные заказа
        if (isset($order['data']) && is_string($order['data'])) {
            $order['data'] = json_decode($order['data'], true);
        }

        // Декодируем JSON-данные в истории статусов
        foreach ($statusHistory as &$record) {
            if (isset($record['platform_data']) && is_string($record['platform_data'])) {
                $record['platform_data'] = json_decode($record['platform_data'], true);
            }
        }

        $this->json([
            'success' => true,
            'order' => $order,
            'statusHistory' => $statusHistory
        ]);
    }

    /**
     * Обновление статуса заказа
     */
    public function updateStatus()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        $orderId = isset($_POST['order_id']) ? $_POST['order_id'] : null;
        $status = isset($_POST['status']) ? $_POST['status'] : null;

        if (!$orderId || !$status) {
            $this->json(['success' => false, 'message' => 'Order ID and status are required']);
            return;
        }

        // Получаем данные заказа
        $order = $this->orderTracker->getOrderById($orderId);

        if (!$order) {
            $this->json(['success' => false, 'message' => 'Order not found']);
            return;
        }

        // Создаем экземпляр сервиса распределения
        $distributionService = new DistributionService();

        // Обновляем статус
        $result = $distributionService->updateOrderStatus(
            $orderId,
            $status,
            'manual',
            ['user' => $_SESSION['user_id'] ?? 'unknown', 'comment' => $_POST['comment'] ?? '']
        );

        $this->json($result);
    }
}