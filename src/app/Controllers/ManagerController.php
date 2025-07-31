<?php




class ManagerController extends BaseController
{
    private $managerModel;
    private $bitrix24Service;
    private $retailCRMService;

    public function __construct()
    {
        $this->managerModel = new Manager();
        $this->bitrix24Service = new Bitrix24Service();
        $this->retailCRMService = new RetailCRMService();
    }

    /**
     * Отображение списка менеджеров
     */
    public function index()
    {
        // Получаем список всех менеджеров
        $managers = $this->managerModel->getAllManagers();

        // Получаем список менеджеров из Битрикс24 и RetailCRM для выпадающих списков
        $bitrix24Managers = $this->bitrix24Service->getUserStatuses();
        $retailCRMManagers = $this->retailCRMService->getManagers();

        // Отображаем представление
        $this->view('managers/index', [
            'pageTitle' => 'Управление менеджерами',
            'managers' => $managers,
            'bitrix24Managers' => $bitrix24Managers,
            'retailCRMManagers' => $retailCRMManagers
        ]);
    }

    /**
     * Добавление нового менеджера
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/managers');
            return;
        }

        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $bitrix24Id = isset($_POST['bitrix24_id']) ? trim($_POST['bitrix24_id']) : '';
        $retailcrmId = isset($_POST['retailcrm_id']) ? trim($_POST['retailcrm_id']) : '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name)) {
            setFlashMessage('Имя менеджера не может быть пустым', 'danger');
            $this->redirect('/managers');
            return;
        }

        $managerData = [
            'name' => $name,
            'bitrix24_id' => $bitrix24Id,
            'retailcrm_id' => $retailcrmId,
            'is_active' => $isActive
        ];

        if ($this->managerModel->addManager($managerData)) {
            setFlashMessage('Менеджер успешно добавлен', 'success');
        } else {
            setFlashMessage('Ошибка при добавлении менеджера', 'danger');
        }

        $this->redirect('/managers');
    }

    /**
     * Обновление менеджера
     */
    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/managers');
            return;
        }

        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $bitrix24Id = isset($_POST['bitrix24_id']) ? trim($_POST['bitrix24_id']) : '';
        $retailcrmId = isset($_POST['retailcrm_id']) ? trim($_POST['retailcrm_id']) : '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name)) {
            setFlashMessage('Имя менеджера не может быть пустым', 'danger');
            $this->redirect('/managers');
            return;
        }

        $managerData = [
            'name' => $name,
            'bitrix24_id' => $bitrix24Id,
            'retailcrm_id' => $retailcrmId,
            'is_active' => $isActive
        ];

        if ($this->managerModel->updateManager($id, $managerData)) {
            setFlashMessage('Менеджер успешно обновлен', 'success');
        } else {
            setFlashMessage('Ошибка при обновлении менеджера', 'danger');
        }

        $this->redirect('/managers');
    }

    /**
     * Удаление менеджера
     */
    public function delete($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/managers');
            return;
        }

        if ($this->managerModel->deleteManager($id)) {
            setFlashMessage('Менеджер успешно удален', 'success');
        } else {
            setFlashMessage('Ошибка при удалении менеджера', 'danger');
        }

        $this->redirect('/managers');
    }
}