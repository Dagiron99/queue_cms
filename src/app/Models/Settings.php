<?php

class Settings
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Получение настроек интеграции
     */
    public function getSettings($integrationType)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT settings 
                FROM integration_settings 
                WHERE type = :type
                LIMIT 1
            ");
            
            $stmt->bindParam(':type', $integrationType, PDO::PARAM_STR);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && !empty($result['settings'])) {
                return json_decode($result['settings'], true);
            }
            
            return [];
            
        } catch (PDOException $e) {
            error_log("Ошибка при получении настроек интеграции: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Сохранение настроек интеграции
     */
    public function saveSettings($integrationType, $settings)
    {
        try {
            // Проверяем, существуют ли уже настройки
            $stmt = $this->db->prepare("
                SELECT id 
                FROM integration_settings 
                WHERE type = :type
                LIMIT 1
            ");
            
            $stmt->bindParam(':type', $integrationType, PDO::PARAM_STR);
            $stmt->execute();
            
            $exists = $stmt->fetchColumn();
            $settingsJson = json_encode($settings);
            
            if ($exists) {
                // Обновляем существующие настройки
                $stmt = $this->db->prepare("
                    UPDATE integration_settings 
                    SET settings = :settings, updated_at = NOW() 
                    WHERE type = :type
                ");
            } else {
                // Добавляем новые настройки
                $stmt = $this->db->prepare("
                    INSERT INTO integration_settings (type, settings, created_at, updated_at) 
                    VALUES (:type, :settings, NOW(), NOW())
                ");
            }
            
            $stmt->bindParam(':type', $integrationType, PDO::PARAM_STR);
            $stmt->bindParam(':settings', $settingsJson, PDO::PARAM_STR);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Ошибка при сохранении настроек интеграции: " . $e->getMessage());
            return false;
        }
    }
}