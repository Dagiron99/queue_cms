<?php


class LogsController {
    private $logModel;
    private $queueModel;
    private $managerModel;
    
    public function __construct() {
        $this->logModel = new DistributionLog();
        $this->queueModel = new Queue();
        $this->managerModel = new Manager();
    }
    
    public function index() {
        // Получаем параметры фильтрации
        $filter = [
            'queue_id' => isset($_GET['queue_id']) ? (int)$_GET['queue_id'] : null,
            'manager_id' => isset($_GET['manager_id']) ? (int)$_GET['manager_id'] : null,
            'status' => isset($_GET['status']) ? $_GET['status'] : null,
            'date_from' => isset($_GET['date_from']) ? $_GET['date_from'] : null,
            'date_to' => isset($_GET['date_to']) ? $_GET['date_to'] : null,
            'search' => isset($_GET['search']) ? $_GET['search'] : null
        ];
        
        // Пагинация
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $logsPerPage = 20;
        $offset = ($page - 1) * $logsPerPage;
        
        // Получаем логи с учетом фильтрации и пагинации
        $logs = $this->logModel->getLogs($filter, $logsPerPage, $offset);
        $totalLogs = $this->logModel->getTotalLogs($filter);
        $totalPages = ceil($totalLogs / $logsPerPage);
        
        // Получаем списки очередей и менеджеров для фильтров
        $queues = $this->queueModel->getAllQueues();
        $managers = $this->managerModel->getAllManagers();
        
        // Рендерим представление
        require_once __DIR__ . '/../../resources/views/logs/index.php';
    }
    
    public function exportCsv() {
        // Получаем параметры фильтрации
        $filter = [
            'queue_id' => isset($_GET['queue_id']) ? (int)$_GET['queue_id'] : null,
            'manager_id' => isset($_GET['manager_id']) ? (int)$_GET['manager_id'] : null,
            'status' => isset($_GET['status']) ? $_GET['status'] : null,
            'date_from' => isset($_GET['date_from']) ? $_GET['date_from'] : null,
            'date_to' => isset($_GET['date_to']) ? $_GET['date_to'] : null,
            'search' => isset($_GET['search']) ? $_GET['search'] : null
        ];
        
        // Получаем все логи без ограничения количества
        $logs = $this->logModel->getLogs($filter);
        
        // Настраиваем заголовки для скачивания CSV-файла
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="distribution_logs_' . date('Y-m-d_H-i-s') . '.csv"');
        
        // Создаем файловый указатель для вывода
        $output = fopen('php://output', 'w');
        
        // Добавляем BOM для правильной кодировки в Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Заголовки CSV
        fputcsv($output, ['ID', 'Очередь', 'Менеджер', 'ID заказа', 'Статус', 'Описание', 'Дата']);
        
        // Данные
        foreach ($logs as $log) {
            $row = [
                $log['id'],
                $log['queue_name'] ?? 'Не указано',
                $log['manager_name'] ?? 'Не указано',
                $log['order_id'],
                $log['status'],
                $log['description'],
                $log['created_at']
            ];
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
}