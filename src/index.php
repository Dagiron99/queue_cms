<?php
// Включаем отображение ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Проверяем, запущена ли уже сессия
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Определяем базовый путь проекта
define('BASE_PATH', __DIR__);

// Подключаем автозагрузчик Composer
require_once BASE_PATH . '/vendor/autoload.php';

// Подключаем Router напрямую, т.к. он еще не переведен на пространства имен
require_once BASE_PATH . '/utils/router.php';

// Инициализация маршрутизатора
$router = new Router();

// Загрузка маршрутов
require_once BASE_PATH . '/routes/web.php';

// Обработка запроса
$router->dispatch();
// Если маршрут не найден, показываем 404
if (!$router->isDispatched()) {
    http_response_code(404);
    echo "404 Not Found";
    exit;
}