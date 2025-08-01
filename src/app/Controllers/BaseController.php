<?php

class BaseController
{
    /**
     * Отображение представления с основным макетом
     */
    protected function view($viewName, $data = [])
    {
        extract($data);

        ob_start();
        include BASE_PATH . '/resources/views/' . $viewName . '.php';
        $content = ob_get_clean();

        include BASE_PATH . '/resources/views/layouts/default.php';
    }

    /**
     * Отображение только представления без макета
     */
    protected function renderPartial($viewName, $data = [])
    {
        extract($data);

        ob_start();
        include BASE_PATH . '/resources/views/' . $viewName . '.php';
        return ob_get_clean();
    }

    /**
     * Перенаправление на другую страницу
     */
    protected function redirect($path)
    {
        header("Location: $path");
        exit;
    }

    /**
     * Ответ в формате JSON
     */
    protected function json($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    /**
     * Форматирование размера файла в читаемый вид
     */
    protected function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}