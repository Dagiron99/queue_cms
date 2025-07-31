<?php

 // Ensure this matches the actual namespace of the User class

namespace App\Services;

use App\Models\User;


class AuthService
{
    public function authenticate($username, $password)
    {
        $user = User::where('username', $username)->first();
        
        if (!$user || !password_verify($password, $user->password)) {
            return false;
        }
        
        // Установка сессии
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    public function isAuthenticated()
    {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
            return false;
        }
        
        // Проверка таймаута сессии (30 минут)
        if (time() - $_SESSION['last_activity'] > 1800) {
            $this->logout();
            return false;
        }
        
        // Обновление времени последней активности
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    public function hasPermission($permission)
    {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        $user = User::find($_SESSION['user_id']);
        $userPermissions = $user->getPermissions();
        
        return in_array($permission, $userPermissions) || 
               in_array('admin', $userPermissions);
    }
    
    public function logout()
    {
        // Уничтожение сессии
        session_unset();
        session_destroy();
        return true;
    }
}