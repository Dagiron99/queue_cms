<?php
/**
 * Маршруты веб-интерфейса
 */

// Главная страница / Дашборд
$router->get('/', ['DashboardController', 'index']);
$router->get('/dashboard/distribution-stats', ['DashboardController', 'distributionStats']);
$router->get('/dashboard/distribution-stats-data', ['DashboardController', 'getDistributionStatsData']);

// Менеджеры
$router->get('/managers', ['ManagerController', 'index']);
$router->post('/managers/store', ['ManagerController', 'store']);
$router->post('/managers/update/{id}', ['ManagerController', 'update']);
$router->post('/managers/delete/{id}', ['ManagerController', 'delete']);

// Очереди
$router->get('/queues', ['QueueController', 'index']);
$router->get('/queues/create', ['QueueController', 'create']);
$router->get('/queues/edit/{id}', ['QueueController', 'edit']);
$router->post('/queues/store', ['QueueController', 'store']);
$router->post('/queues/update/{id}', ['QueueController', 'update']);
$router->post('/queues/delete/{id}', ['QueueController', 'delete']);
$router->post('/queues/reset/{id}', ['QueueController', 'reset']);
$router->post('/queues/add-manager', ['QueueController', 'addManager']);
$router->post('/queues/remove-manager', ['QueueController', 'removeManager']);
$router->post('/queues/update-priority', ['QueueController', 'updatePriority']);
$router->post('/queues/update-specializations', ['QueueController', 'updateSpecializations']);

// Логи
$router->get('/logs', ['LogController', 'index']);
$router->post('/logs/clear', ['LogController', 'clear']);

// Настройки
$router->get('/settings', ['SettingsController', 'index']);
$router->get('/settings/integration', ['SettingsController', 'integration']);
$router->post('/settings/update', ['SettingsController', 'update']);
$router->post('/settings/create', ['SettingsController', 'create']);
$router->post('/settings/delete', ['SettingsController', 'delete']);
$router->post('/settings/save', ['SettingsController', 'save']);
$router->post('/settings/test-integration', ['SettingsController', 'testIntegration']);
$router->post('/api/test-connection', ['SettingsController', 'testConnection']);

// Отслеживание заказов
$router->get('/orders_tracking', ['OrderTrackingController', 'index']);

$router->get('/login', ['AuthController', 'loginForm']);
$router->post('/login', ['AuthController', 'login']);
$router->get('/logout', ['AuthController', 'logout']);
$router->get('/access-denied', ['AuthController', 'accessDenied']);