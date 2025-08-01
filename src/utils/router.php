<?php
class Router {
    private $routes = [];

    /**
     * Добавление GET-маршрута
     */
    public function get($path, $callback) {
        $this->routes['GET'][$path] = $callback;
    }

    /**
     * Добавление POST-маршрута
     */
    public function post($path, $callback) {
        $this->routes['POST'][$path] = $callback;
    }

    /**
     * Разрешение маршрута на основе URL
     */
    public function resolve($url) {
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Удаляем query parameters из URL
        $url = parse_url($url, PHP_URL_PATH);
        
        // Проверяем точное совпадение маршрута
        if (isset($this->routes[$method][$url])) {
            return $this->executeCallback($this->routes[$method][$url]);
        }
        
        // Проверяем маршруты с параметрами
        foreach ($this->routes[$method] as $route => $callback) {
            $pattern = $this->convertRouteToRegex($route);
            if (preg_match($pattern, $url, $matches)) {
                array_shift($matches); // Удаляем полное совпадение
                return $this->executeCallback($callback, $matches);
            }
        }
        
        // Если маршрут не найден, показываем страницу 404
        http_response_code(404);
        include_once BASE_PATH . '/resources/views/errors/404.php';
        return null;
    }
    
    /**
     * Конвертация шаблона маршрута в регулярное выражение
     */
    private function convertRouteToRegex($route) {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route);
        return '@^' . $pattern . '$@';
    }
    
    /**
     * Выполнение функции обратного вызова для маршрута
     */
    private function executeCallback($callback, $params = []) {
        if (is_callable($callback)) {
            return call_user_func_array($callback, $params);
        }
        
        if (is_array($callback) && count($callback) === 2) {
            $controllerName = $callback[0];
            $methodName = $callback[1];
            
            // ИСПРАВЛЕНИЕ: Проверяем, содержит ли контроллер путь к директории
            if (strpos($controllerName, '/') === false) {
                // Если путь не указан, добавляем путь к директории контроллеров
                $controllerFile = BASE_PATH . '/app/Controllers/' . $controllerName . '.php';
            } else {
                $controllerFile = BASE_PATH . '/' . $controllerName . '.php';
            }
            
            // Проверяем существование файла
            if (file_exists($controllerFile)) {
                require_once $controllerFile;
            } else {
                // Если файл не найден, выводим информативную ошибку
                echo "Error: Controller file not found: $controllerFile";
                return null;
            }
            
            // Создаем экземпляр контроллера
            $controller = new $controllerName();
            
            // Проверяем, существует ли метод
            if (method_exists($controller, $methodName)) {
                return call_user_func_array([$controller, $methodName], $params);
            } else {
                echo "Error: Method $methodName not found in $controllerName";
                return null;
            }
        }
        
        return null;
    }
}