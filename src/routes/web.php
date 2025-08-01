<?php
/**
 * Маршруты веб-интерфейса
 */

// Главная страница / Дашборд
$router->get('/', ['DashboardController', 'index']);

// Менеджеры
$router->get('/managers', ['ManagerController', 'index']);
$router->post('/managers/store', ['ManagerController', 'store']);
$router->post('/managers/update/{id}', ['ManagerController', 'update']);
$router->post('/managers/delete/{id}', ['ManagerController', 'delete']);

// Очереди
$router->get('/queues', ['QueueController', 'index']);
$router->post('/queues/store', ['QueueController', 'store']);

$router->post('/queues/delete/{id}', ['QueueController', 'delete']);
$router->post('/queues/reset/{id}', ['QueueController', 'reset']);

$router->post('/queues/add-manager', ['QueueController', 'addManager']);

$router->post('/queues/remove-manager', ['QueueController', 'removeManager']);
$router->post('/queues/update', ['QueueController', 'update']);
$router->post('/queues/update/{id}', ['QueueController', 'update']);

// Логи
$router->get('/logs/', ['LogsController', 'index']);
$router->post('/logs/export-csv', ['LogsController', 'exportCsv']);

// Настройки
$router->get('/settings', ['SettingsController', 'index']);
$router->post('/settings/update', ['SettingsController', 'update']);
$router->post('/settings/create', ['SettingsController', 'create']);
$router->post('/settings/delete', ['SettingsController', 'delete']);
$router->post('/settings/test-integration', ['SettingsController', 'testIntegration']);

// Настройки интеграций - НОВЫЕ МАРШРУТЫ
$router->get('/settings/integration', ['SettingsController', 'integration']);
$router->post('/settings/save', ['SettingsController', 'save']);
$router->post('/api/test-connection', ['SettingsController', 'testConnection']);

// Отслеживание заказов
$router->get('/orders_tracking', ['OrderTrackingController', 'index']);