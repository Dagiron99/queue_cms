<?php
/**
 * Файл с вспомогательными функциями
 */

/**
 * Установка flash-сообщения
 * 
 * @param string $message Текст сообщения
 * @param string $type Тип сообщения (success, danger, warning, info)
 */
function setFlashMessage($message, $type = 'info') {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    
    $_SESSION['flash_messages'][] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Получение всех flash-сообщений и их очистка
 * 
 * @return array Массив сообщений
 */
function getFlashMessages() {
    $messages = isset($_SESSION['flash_messages']) ? $_SESSION['flash_messages'] : [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Проверка, является ли строка JSON
 * 
 * @param string $string Строка для проверки
 * @return bool
 */
function isJson($string) {
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

/**
 * Безопасное получение значения из массива
 * 
 * @param array $array Массив
 * @param string $key Ключ
 * @param mixed $default Значение по умолчанию
 * @return mixed
 */
function arrayGet($array, $key, $default = null) {
    return isset($array[$key]) ? $array[$key] : $default;
}

/**
 * Логирование в файл
 * 
 * @param string $message Сообщение для логирования
 * @param string $filename Имя файла лога
 */
function logToFile($message, $filename = 'app.log') {
    $logDir = BASE_PATH . '/storage/logs';
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $logFile = $logDir . '/' . $filename;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Получение URL текущей страницы
 * 
 * @return string
 */
function getCurrentUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    
    return $protocol . $domainName . $uri;
}

/**
 * Перенаправление на другую страницу
 * 
 * @param string $url URL для перенаправления
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Форматирование даты и времени
 * 
 * @param string $dateTime Дата и время в формате MySQL
 * @param string $format Формат вывода
 * @return string
 */
function formatDateTime($dateTime, $format = 'd.m.Y H:i:s') {
    return date($format, strtotime($dateTime));
}

/**
 * Очистка и санитизация входных данных
 * 
 * @param mixed $data Данные для очистки
 * @return mixed
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeInput($value);
        }
    } else {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    return $data;
}

/**
 * Проверка, является ли запрос AJAX-запросом
 * 
 * @return bool
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Генерация случайной строки
 * 
 * @param int $length Длина строки
 * @return string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString;
}

if (!function_exists('getCurrentUser')) {
    function getCurrentUser()
    {
        // Ваша логика получения текущего пользователя
        // Например, из сессии:
        return $_SESSION['user'] ?? null;
    }
}