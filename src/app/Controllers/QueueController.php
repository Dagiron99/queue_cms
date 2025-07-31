<?php
namespace App\Controllers;

use App\Models\Queue;
use App\Models\Manager;
use App\Services\Logger;
class QueueController extends BaseController
{
    private $queueModel;
    private $managerModel;
    private $logger;

    public function __construct()
    {
        $this->queueModel = new Queue();
        $this->managerModel = new Manager();
        $this->logger = new Logger('queue_controller');
    }

    /**
     * Отображение списка очередей
     */
    public function index()
    {
        // Получаем список всех очередей
        $queues = $this->queueModel->getAllQueues();
        $availableManagers = $this->managerModel->getAvailableManagers();
        $queueManagersMap = [];

        $this->logger->info("QueueController: Получено очередей: " . count($queues));

        foreach ($queues as &$queue) {
            if (!isset($queue['algorithm'])) {
                $queue['algorithm'] = 'round_robin';
                $this->logger->info("Для очереди ID={$queue['id']} установлен algorithm по умолчанию");
            }
            if (!isset($queue['is_active'])) {
                $queue['is_active'] = 0;
                $this->logger->info("Для очереди ID={$queue['id']} установлен is_active по умолчанию");
            }
            if (!isset($queue['current_position'])) {
                $queue['current_position'] = 1;
                $this->logger->info("Для очереди ID={$queue['id']} установлен current_position по умолчанию");
            }
            $queue['distribution_stats'] = $this->queueModel->getQueueDistributionStats($queue['id']);
            // Добавляем менеджеров для очереди
            $queueManagersMap[$queue['id']] = $this->queueModel->getManagersForQueue($queue['id']);
        }

        $this->view('queues/index', [
            'pageTitle' => 'Управление очередями',
            'queues' => $queues,
            'queueManagersMap' => $queueManagersMap,
            'availableManagers' => $availableManagers, // <-- обязательно!
        ]);
    }

    /**
     * Создание новой очереди (отображение формы)
     */
    public function create()
    {
        $categories = $this->managerModel->getAllCategories();

        $this->view('queues/form', [
            'pageTitle' => 'Создание очереди',
            'categories' => $categories,
        ]);
    }

    /**
     * Редактирование очереди (отображение формы)
     */
    public function edit($id)
    {
        $queue = $this->queueModel->getQueueById($id);
        if (!$queue) {
            setFlashMessage('Очередь не найдена', 'danger');
            $this->redirect('/queues');
            return;
        }

        $categories = $this->managerModel->getAllCategories();
        $queueManagers = $this->queueModel->getManagersForQueue($id);

        $this->view('queues/edit_form', [
            'pageTitle' => 'Редактирование очереди',
            'queue' => $queue,
            'queueManagers' => $queueManagers,
            'categories' => $categories,
        ]);
    }

    /**
     * Добавление новой очереди
     */
    public function store()
    {
        $data = [
            'name' => $_POST['name'] ?? '',
            'algorithm' => $_POST['algorithm'] ?? 'round_robin',
            'is_active' => isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1,
            'max_load' => isset($_POST['max_load']) ? (int) $_POST['max_load'] : 2,
            'type' => $_POST['type'] ?? 'online'
        ];

        $queueId = $this->queueModel->addQueue($data);
        if ($queueId) {
            setFlashMessage('Очередь успешно создана', 'success');
        } else {
            setFlashMessage('Ошибка при создании очереди', 'danger');
        }
        $this->redirect('/queues');
    }

    /**
     * Обновление очереди
     */
    public function update($id = null)
    {
        // Если id не пришёл через URL, берём из POST
        if ($id === null) {
            $id = $_POST['id'] ?? null;
        }

        if (!$id) {
            setFlashMessage('ID очереди не указан', 'danger');
            $this->redirect('/queues');
            return;
        }

        $data = [
            'name' => $_POST['name'] ?? '',
            'algorithm' => $_POST['algorithm'] ?? 'round_robin',
            'is_active' => isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1,
            'max_load' => isset($_POST['max_load']) ? (int) $_POST['max_load'] : 2,
            'type' => $_POST['type'] ?? 'online'
        ];

        if ($this->queueModel->updateQueue($id, $data)) {
            setFlashMessage('Очередь успешно обновлена', 'success');
        } else {
            setFlashMessage('Ошибка при обновлении очереди', 'danger');
        }
        $this->redirect('/queues');
    }

    /**
     * Удаление очереди
     */
    public function delete($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/queues');
            return;
        }

        if ($this->queueModel->deleteQueue($id)) {
            setFlashMessage('Очередь успешно удалена', 'success');
        } else {
            setFlashMessage('Ошибка при удалении очереди', 'danger');
        }

        $this->redirect('/queues');
    }

    /**
     * Сброс позиции очереди
     */
    public function reset($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/queues');
            return;
        }

        if ($this->queueModel->resetQueuePosition($id)) {
            setFlashMessage('Позиция очереди успешно сброшена', 'success');
        } else {
            setFlashMessage('Ошибка при сбросе позиции очереди', 'danger');
        }

        $this->redirect('/queues');
    }

    /**
     * Добавление менеджера в очередь
     */
    public function addManager()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/queues');
            return;
        }

        $queueId = (int) ($_POST['queue_id'] ?? 0);
        $managerId = (int) ($_POST['manager_id'] ?? 0);
        $priority = (int) ($_POST['priority'] ?? 10);

        $queue = $this->queueModel->getQueueById($queueId);
        $maxLoad = $queue ? (int) $queue['max_load'] : 2;

        // Fallback только если очередь типа online-fallback, чекбокс is_fallback в форме
        $isFallback = 0;
        if ($queue && $queue['type'] === 'online-fallback') {
            $isFallback = isset($_POST['is_fallback']) ? 1 : 0;
        }

        if ($queueId > 0 && $managerId > 0) {
            $result = $this->queueModel->setManagerQueueRelation($queueId, $managerId, $maxLoad, $isFallback, $priority);
            if ($result) {
                setFlashMessage('Менеджер успешно добавлен в очередь', 'success');
            } else {
                setFlashMessage('Ошибка при добавлении менеджера в очередь', 'danger');
            }
        } else {
            setFlashMessage('Некорректные данные для добавления менеджера в очередь', 'danger');
        }

        $this->redirect('/queues');
    }

    /**
     * Получить список менеджеров, которых еще нет в очереди
     */
    public function getAvailableManagersForQueue($queueId)
    {
        return $this->managerModel->getAvailableManagersForQueue($queueId);
    }

    /**
     * Удаление менеджера из очереди
     */
    public function removeManager()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/queues');
            return;
        }

        $queueId = (int) ($_POST['queue_id'] ?? 0);
        $managerId = (int) ($_POST['manager_id'] ?? 0);

        if ($queueId > 0 && $managerId > 0) {
            $result = $this->queueModel->removeManagerFromQueue($queueId, $managerId);
            if ($result) {
                setFlashMessage('Менеджер успешно удалён из очереди', 'success');
            } else {
                setFlashMessage('Ошибка при удалении менеджера из очереди', 'danger');
            }
        } else {
            setFlashMessage('Некорректные данные для удаления менеджера из очереди', 'danger');
        }

        $this->redirect('/queues');
    }

    /**
     * Обновление приоритета менеджера в очереди
     */
    public function updatePriority()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        // Получаем данные из JSON-запроса
        $data = json_decode(file_get_contents('php://input'), true);

        // Проверяем наличие всех необходимых полей
        if (!isset($data['queue_id']) || !isset($data['manager_id']) || !isset($data['priority'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
        }

        // Валидация приоритета
        $priority = (int) $data['priority'];
        if ($priority < 1 || $priority > 100) {
            return $this->jsonResponse(['success' => false, 'message' => 'Priority must be between 1 and 100'], 400);
        }

        // Обновляем приоритет менеджера в очереди
        $success = $this->queueModel->updateManagerPriority(
            $data['queue_id'],
            $data['manager_id'],
            $priority
        );

        if ($success) {
            $this->logger->info("Обновлен приоритет менеджера", [
                'queue_id' => $data['queue_id'],
                'manager_id' => $data['manager_id'],
                'priority' => $priority
            ]);

            return $this->jsonResponse(['success' => true]);
        } else {
            $this->logger->error("Ошибка при обновлении приоритета менеджера", [
                'queue_id' => $data['queue_id'],
                'manager_id' => $data['manager_id'],
                'priority' => $priority
            ]);

            return $this->jsonResponse(['success' => false, 'message' => 'Failed to update priority']);
        }
    }

    /**
     * Обновление специализаций менеджера
     */
    public function updateSpecializations()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        // Получаем данные из JSON-запроса
        $data = json_decode(file_get_contents('php://input'), true);

        // Проверяем наличие всех необходимых полей
        if (!isset($data['manager_id']) || !isset($data['specializations'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
        }

        // Обновляем специализации менеджера
        $success = $this->managerModel->updateSpecializations(
            $data['manager_id'],
            $data['specializations']
        );

        if ($success) {
            $this->logger->info("Обновлены специализации менеджера", [
                'manager_id' => $data['manager_id'],
                'specializations' => implode(', ', $data['specializations'])
            ]);

            return $this->jsonResponse(['success' => true]);
        } else {
            $this->logger->error("Ошибка при обновлении специализаций менеджера", [
                'manager_id' => $data['manager_id']
            ]);

            return $this->jsonResponse(['success' => false, 'message' => 'Failed to update specializations']);
        }
    }

    /**
     * Отправляет JSON-ответ
     */
    protected function jsonResponse($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}