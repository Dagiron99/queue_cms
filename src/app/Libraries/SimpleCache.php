<?php

namespace App\Libraries;

/**
 * Простой класс для кеширования данных
 */
class SimpleCache
{
    private $cacheDir;
    private $ttl;

    /**
     * Инициализация кеша
     * 
     * @param string $cacheDir Директория для файлов кеша
     * @param int $ttl Время жизни кеша в секундах (по умолчанию 3600 - 1 час)
     */
    public function __construct($cacheDir = null, $ttl = 3600)
    {
        $this->cacheDir = $cacheDir ?? __DIR__ . '/../../cache';
        $this->ttl = $ttl;
        
        // Создаем директорию кеша, если она не существует
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Получение данных из кеша
     * 
     * @param string $key Ключ кеша
     * @return mixed|null Данные из кеша или null, если кеш не найден или устарел
     */
    public function get($key)
    {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        // Проверяем время создания файла
        if (time() - filemtime($filename) > $this->ttl) {
            @unlink($filename);
            return null;
        }
        
        $data = file_get_contents($filename);
        return unserialize($data);
    }

    /**
     * Сохранение данных в кеш
     * 
     * @param string $key Ключ кеша
     * @param mixed $data Данные для кеширования
     * @return bool Результат операции
     */
    public function set($key, $data)
    {
        $filename = $this->getCacheFilename($key);
        $serialized = serialize($data);
        
        return file_put_contents($filename, $serialized, LOCK_EX) !== false;
    }

    /**
     * Удаление данных из кеша
     * 
     * @param string $key Ключ кеша
     * @return bool Результат операции
     */
    public function delete($key)
    {
        $filename = $this->getCacheFilename($key);
        
        if (file_exists($filename)) {
            return @unlink($filename);
        }
        
        return true;
    }

    /**
     * Очистка всего кеша
     * 
     * @return bool Результат операции
     */
    public function clear()
    {
        $files = glob($this->cacheDir . '/*.cache');
        
        foreach ($files as $file) {
            @unlink($file);
        }
        
        return true;
    }

    /**
     * Формирование имени файла кеша
     * 
     * @param string $key Ключ кеша
     * @return string Полный путь к файлу кеша
     */
    private function getCacheFilename($key)
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
}