<?php

namespace App\Services;

class Logger
{
    private $logFile;
    
    public function __construct($filename = 'app.log')
    {
        $this->logFile = BASE_PATH . '/storage/logs/' . $filename;
        
        // Создаем директорию для логов, если её нет
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Запись сообщения в лог
     * 
     * @param string $message Сообщение для записи
     * @param string $level Уровень логирования (info, warning, error, debug)
     * @return bool Успешность записи
     */
    public function log($message, $level = 'info')
    {
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[$timestamp] [$level] $message" . PHP_EOL;

        return file_put_contents($this->logFile, $formattedMessage, FILE_APPEND);
    }

    /**
     * Запись информационного сообщения
     */
    public function info($message)
    {
        return $this->log($message, 'info');
    }

    /**
     * Запись предупреждения
     */
    public function warning($message)
    {
        return $this->log($message, 'warning');
    }

    /**
     * Запись ошибки
     */
    public function error($message)
    {
        return $this->log($message, 'error');
    }

    /**
     * Запись отладочной информации
     */
    public function debug($message)
    {
        return $this->log($message, 'debug');
    }

    /**
     * Очистка лог-файла
     */
    public function clear()
    {
        return file_put_contents($this->logFile, '');
    }

    /**
     * Получение содержимого лог-файла
     */
    public function getContents()
    {
        if (!file_exists($this->logFile)) {
            return '';
        }

        return file_get_contents($this->logFile);
    }

    /**
     * Получение последних N строк лога
     */
    public function getLastLines($count = 100)
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $lines = [];
        $fp = fopen($this->logFile, 'r');

        while (!feof($fp)) {
            $line = fgets($fp);
            if ($line !== false) {
                $lines[] = $line;

                // Ограничиваем количество строк в памяти
                if (count($lines) > $count) {
                    array_shift($lines);
                }
            }
        }

        fclose($fp);

        return $lines;
    }

    /**
     * Получение имени лог-файла
     */
    public function getLogFileName()
    {
        return basename($this->logFile);
    }

    /**
     * Получение размера лог-файла
     */
    public function getLogFileSize()
    {
        if (!file_exists($this->logFile)) {
            return 0;
        }

        return filesize($this->logFile);
    }
}