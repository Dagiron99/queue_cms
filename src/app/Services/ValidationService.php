<?php

namespace App\Services;

class ValidationService
{
    private $errors = [];
    
    public function validate($data, $rules)
    {
        $this->errors = [];
        
        foreach ($rules as $field => $rule) {
            $ruleArray = explode('|', $rule);
            
            foreach ($ruleArray as $singleRule) {
                $params = [];
                
                // Проверка параметров правила (например, max:255)
                if (strpos($singleRule, ':') !== false) {
                    list($ruleName, $ruleParams) = explode(':', $singleRule, 2);
                    $params = explode(',', $ruleParams);
                } else {
                    $ruleName = $singleRule;
                }
                
                $methodName = 'validate' . ucfirst($ruleName);
                
                if (method_exists($this, $methodName)) {
                    if (!isset($data[$field]) && $ruleName !== 'required') {
                        continue;
                    }
                    
                    $value = isset($data[$field]) ? $data[$field] : null;
                    
                    if (!$this->$methodName($value, $params)) {
                        $this->addError($field, $ruleName, $params);
                    }
                }
            }
        }
        
        return empty($this->errors);
    }
    
    private function addError($field, $rule, $params = [])
    {
        $message = $this->getErrorMessage($field, $rule, $params);
        $this->errors[$field][] = $message;
    }
    
    public function getErrors()
    {
        return $this->errors;
    }
    
    private function getErrorMessage($field, $rule, $params)
    {
        $messages = [
            'required' => 'Поле обязательно для заполнения',
            'email' => 'Некорректный формат email',
            'min' => 'Минимальная длина: ' . $params[0],
            'max' => 'Максимальная длина: ' . $params[0],
            'integer' => 'Должно быть целым числом',
            'numeric' => 'Должно быть числом',
            'url' => 'Некорректный формат URL',
            'json' => 'Некорректный формат JSON'
        ];
        
        return $messages[$rule] ?? 'Поле не соответствует правилу: ' . $rule;
    }
    
    // Методы валидации
    private function validateRequired($value)
    {
        return $value !== null && $value !== '';
    }
    
    private function validateEmail($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    private function validateMin($value, $params)
    {
        $min = (int) $params[0];
        return strlen($value) >= $min;
    }
    
    private function validateMax($value, $params)
    {
        $max = (int) $params[0];
        return strlen($value) <= $max;
    }
    
    private function validateInteger($value)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    private function validateNumeric($value)
    {
        return is_numeric($value);
    }
    
    private function validateUrl($value)
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
    
    private function validateJson($value)
    {
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }
}