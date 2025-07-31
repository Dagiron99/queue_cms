<?php
namespace App\Controllers;

use App\Models\Settings;

class SettingsController extends BaseController
{
    private $Settings;
    
    public function __construct()
    {
        $this->Settings = new Settings();
    }
    
    /**
     * Отображение главной страницы настроек
     */
    public function index()
    {
        $this->view('settings/index', [
            'pageTitle' => 'Панель управления',
            'Settings' => $this->Settings,
            'currentDate' => '2025-07-31 02:14:42',
            'currentUser' => 'Dagiron99'
        ]);
    }
    
    /**
     * Отображение страницы настроек интеграций
     */
    public function integration()
    {
        $this->view('settings/integration', [
            'pageTitle' => 'Настройки интеграций',
            'Settings' => $this->Settings,
            'currentDate' => date('Y-m-d H:i:s'),
            'currentUser' => $_SESSION['user_login'] ?? 'Dagiron99'
        ]);
    }
    
    /**
     * Сохранение настроек интеграции
     */
    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/settings/integration');
            return;
        }
        
        $integrationType = $_POST['integration_type'] ?? '';
        $settings = $_POST['settings'] ?? [];
        
        if (empty($integrationType) || empty($settings)) {
            $_SESSION['error'] = 'Некорректные данные формы.';
            $this->redirect('/settings/integration');
            return;
        }
        
        // Сохраняем настройки
        $result = $this->Settings->saveSettings($integrationType, $settings);
        
        if ($result) {
            $_SESSION['success'] = 'Настройки успешно сохранены.';
        } else {
            $_SESSION['error'] = 'Ошибка при сохранении настроек.';
        }
        
        $this->redirect('/settings/integration?tab=' . $integrationType);
    }
    
    /**
     * Тестирование соединения с внешней системой
     */
    public function testConnection()
    {
        // Получаем данные из запроса
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['type'])) {
            $this->json(['success' => false, 'message' => 'Не указан тип интеграции']);
            return;
        }
        
        // Тестируем соединение в зависимости от типа
        switch ($data['type']) {
            case 'bitrix24':
                $result = $this->testBitrixConnection($data);
                break;
                
            case 'retailcrm':
                $result = $this->testRetailCrmConnection($data);
                break;
                
            default:
                $result = ['success' => false, 'message' => 'Неизвестный тип интеграции'];
        }
        
        $this->json($result);
    }
    
    /**
     * Тестирование соединения с Битрикс24
     */
    private function testBitrixConnection($data)
    {
        if (empty($data['portal_url']) || empty($data['webhook'])) {
            return ['success' => false, 'message' => 'Не указаны обязательные параметры'];
        }
        
        try {
            // Здесь код проверки соединения с Битрикс24
            // Для примера вернем успешный результат
            return [
                'success' => true,
                'portal_name' => 'Тестовый портал Битрикс24',
                'version' => '22.0.1'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Тестирование соединения с RetailCRM
     */
    private function testRetailCrmConnection($data)
    {
        if (empty($data['url']) || empty($data['api_key'])) {
            return ['success' => false, 'message' => 'Не указаны обязательные параметры'];
        }
        
        try {
            // Здесь код проверки соединения с RetailCRM
            // Для примера вернем успешный результат
            return [
                'success' => true,
                'crm_name' => 'Тестовый аккаунт RetailCRM',
                'version' => '6.2.0'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}