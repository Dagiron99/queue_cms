<?php


class AuthMiddleware
{
    private $authService;
    
    public function __construct()
    {
        $this->authService = new AuthService();
    }
    
    public function handle($request, $next)
    {
        if (!$this->authService->isAuthenticated()) {
            // Перенаправление на страницу входа
            header('Location: /login');
            exit;
        }
        
        return $next($request);
    }
    
    public function handleWithPermission($request, $next, $permission)
    {
        if (!$this->authService->isAuthenticated()) {
            header('Location: /login');
            exit;
        }
        
        if (!$this->authService->hasPermission($permission)) {
            // Страница с ошибкой доступа
            header('Location: /access-denied');
            exit;
        }
        
        return $next($request);
    }
}