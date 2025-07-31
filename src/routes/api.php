<?php
/**
 * Маршруты API
 */

// Распределение заказа
$router->post('/api/distribute', ['ApiController', 'distributeOrder']);

// Получение данных менеджера
$router->get('/api/manager/{id}', ['ApiController', 'getManager']);

// Получение статуса очереди
$router->get('/api/queue/{id}', ['ApiController', 'getQueueStatus']);

// Webhook для Bitrix24
$router->post('/api/bitrix24/webhook', ['ApiController', 'processBitrix24Webhook']);

// Дополнительные маршруты для OrderTracking
$router->get('/orders_tracking/get-details', ['OrderTrackingController', 'getDetails']);
$router->post('/orders_tracking/update-status', ['OrderTrackingController', 'updateStatus']);