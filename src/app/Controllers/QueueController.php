<?php




class QueueController extends BaseController
{
    private $queueModel;
    private $managerModel;

    public function __construct()
    {
        $this->queueModel = new Queue();
        $this->managerModel = new Manager();
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

        logToFile("QueueController: Получено очередей: " . count($queues), 'app.log');

        foreach ($queues as &$queue) {
            if (!isset($queue['algorithm'])) {
                $queue['algorithm'] = 'round_robin';
                logToFile("Для очереди ID={$queue['id']} установлен algorithm по умолчанию", 'app.log');
            }
            if (!isset($queue['is_active'])) {
                $queue['is_active'] = 0;
                logToFile("Для очереди ID={$queue['id']} установлен is_active по умолчанию", 'app.log');
            }
            if (!isset($queue['current_position'])) {
                $queue['current_position'] = 1;
                logToFile("Для очереди ID={$queue['id']} установлен current_position по умолчанию", 'app.log');
            }
            $queue['distribution_stats'] = $this->queueModel->getQueueDistributionStats($queue['id']) ?? [];
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
     * Добавление новой очереди
     */
    public function store()
    {
        $data = [
            'name' => $_POST['name'] ?? '',
            'algorithm' => $_POST['algorithm'] ?? 'linear',
            'is_active' => isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1,
            'max_load' => isset($_POST['max_load']) ? (int) $_POST['max_load'] : 2,
            'type' => $_POST['type'] ?? 'online'
        ];

        $this->queueModel->addQueue($data);
        setFlashMessage('Очередь создана', 'success');
        $this->redirect('/queues');
    }

    /**
     * Обновление очереди
     */    // Редактирование очереди
    public function update($id = null)
    {

        // Если id не пришёл через URL, берём из POST
        if ($id === null) {
            $id = $_POST['id'] ?? null;
        }


        $data = [
            'name' => $_POST['name'] ?? '',
            'algorithm' => $_POST['algorithm'] ?? 'linear',
            'is_active' => isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1,
            'max_load' => isset($_POST['max_load']) ? (int) $_POST['max_load'] : 2,
            'type' => $_POST['type'] ?? 'online'
        ];

        $this->queueModel->updateQueue($id, $data);
        setFlashMessage('Очередь обновлена', 'success');
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
    // Добавление менеджера в очередь
    public function addManager()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/queues');
            return;
        }

        $queueId = (int) ($_POST['queue_id'] ?? 0);
        $managerId = (int) ($_POST['manager_id'] ?? 0);

        $queue = $this->queueModel->getQueueById($queueId);
        $maxLoad = $queue ? (int) $queue['max_load'] : 2;

        // Fallback только если очередь типа online-fallback, чекбокс is_fallback в форме
        $isFallback = 0;
        if (is_array($queue) && $queue['type'] === 'online-fallback') {
            $isFallback = isset($_POST['is_fallback']) ? 1 : 0;
        }

        if ($queueId > 0 && $managerId > 0) {
            $result = $this->queueModel->setManagerQueueRelation($queueId, $managerId, $maxLoad, $isFallback);
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
    // Получить список менеджеров, которых еще нет в очереди
    public function getAvailableManagersForQueue($queueId)
    {
        $all = $this->managerModel->getAllManagers();
        $added = $this->queueModel->getManagersForQueue($queueId);
        $addedIds = array_column($added, 'id');
        return array_filter($all, fn($m) => !in_array($m['id'], $addedIds));
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
}