<?php

namespace App\Services;
/**
 * CSRFService - Service for handling CSRF protection
 */
class CSRFService
{
    public function generateToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    public function validateToken($token)
    {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            return false;
        }
        
        return true;
    }
    
    public function getTokenField()
    {
        $token = $this->generateToken();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}