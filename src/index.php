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

// Подключаем вспомогательные функции
require_once BASE_PATH . '/utils/helpers.php';
require_once BASE_PATH . '/utils/router.php';


// Прямая загрузка базовых классов
require_once BASE_PATH . '/app/Controllers/BaseController.php';
require_once BASE_PATH . '/app/Models/Database.php';
require_once BASE_PATH . '/app/Models/Settings.php';

// Автозагрузка других классов
spl_autoload_register(function ($className) {
    // Удаляем обратный слеш в начале, если он есть
    $className = ltrim($className, '\\');
    
    // Проверяем на пространство имен и извлекаем имя класса
    if (strpos($className, '\\') !== false) {
        $parts = explode('\\', $className);
        $className = end($parts);
    }
    
    // Проверяем различные пути для классов
    $paths = [
        BASE_PATH . '/app/Controllers/' . $className . '.php',
        BASE_PATH . '/app/Models/' . $className . '.php',
        BASE_PATH . '/app/Services/' . $className . '.php',
        BASE_PATH . '/' . $className . '.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Инициализация маршрутизатора
$router = new Router();

// Загрузка маршрутов
require_once BASE_PATH . '/routes/web.php';
require_once BASE_PATH . '/routes/api.php';

// Обработка запроса
$router->resolve($_SERVER['REQUEST_URI']);