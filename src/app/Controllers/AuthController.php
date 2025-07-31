<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\CSRFService;
use App\Services\ValidationService;

class AuthController
{
    private $authService;
    private $csrfService;
    private $validationService;
    
    public function __construct()
    {
        $this->authService = new AuthService();
        $this->csrfService = new CSRFService();
        $this->validationService = new ValidationService();
    }
    
    public function loginForm()
    {
        // Отображение формы входа с CSRF-токеном
        include __DIR__ . '/../views/login.php';
    }
    
    public function login()
    {
        // Проверка CSRF-токена
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($csrfToken)) {
            $_SESSION['error'] = 'Ошибка безопасности. Пожалуйста, попробуйте снова.';
            header('Location: /login');
            exit;
        }
        
        // Валидация данных формы
        $rules = [
            'username' => 'required',
            'password' => 'required'
        ];
        
        if (!$this->validationService->validate($_POST, $rules)) {
            $_SESSION['error'] = 'Заполните все обязательные поля';
            header('Location: /login');
            exit;
        }
        
        // Аутентификация
        if ($this->authService->authenticate($_POST['username'], $_POST['password'])) {
            header('Location: /dashboard');
        } else {
            $_SESSION['error'] = 'Неверное имя пользователя или пароль';
            header('Location: /login');
        }
        
        exit;
    }
    
    public function logout()
    {
        $this->authService->logout();
        header('Location: /login');
        exit;
    }
}