<?php


class LogController extends BaseController
{
    private $logDirectory;

    public function __construct()
    {
        $this->logDirectory = BASE_PATH . '/storage/logs';
    }

    /**
     * Отображение списка логов
     */
    public function index()
    {
        // Получаем список файлов логов
        $logFiles = $this->getLogFiles();

        // Получаем содержимое выбранного файла
        $selectedLog = isset($_GET['file']) ? $_GET['file'] : null;
        $logContent = $this->getLogContent($selectedLog);

        // Отображаем представление
        $this->view('logs/index', [
            'pageTitle' => 'Журнал системных событий',
            'logFiles' => $logFiles,
            'selectedLog' => $selectedLog,
            'logContent' => $logContent
        ]);
    }

    /**
     * Очистка выбранного лог-файла
     */
    public function clear()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/logs');
            return;
        }

        $file = isset($_POST['file']) ? $_POST['file'] : null;

        if ($file && $this->isValidLogFile($file)) {
            $filePath = $this->logDirectory . '/' . $file;

            if (file_exists($filePath)) {
                // Очищаем файл, сохраняя пустой файл
                file_put_contents($filePath, '');
                setFlashMessage('Лог-файл успешно очищен', 'success');
            } else {
                setFlashMessage('Лог-файл не найден', 'danger');
            }
        } else {
            setFlashMessage('Неверный файл лога', 'danger');
        }

        $this->redirect('/logs' . ($file ? '?file=' . urlencode($file) : ''));
    }

    /**
     * Получение списка лог-файлов
     */
    private function getLogFiles()
    {
        $files = [];

        if (is_dir($this->logDirectory)) {
            $items = scandir($this->logDirectory);

            foreach ($items as $item) {
                // Исключаем . и .. и скрытые файлы
                if ($item[0] !== '.' && is_file($this->logDirectory . '/' . $item)) {
                    $files[] = [
                        'name' => $item,
                        'size' => filesize($this->logDirectory . '/' . $item),
                        'modified' => filemtime($this->logDirectory . '/' . $item)
                    ];
                }
            }

            // Сортируем по времени модификации (новые вначале)
            usort($files, function ($a, $b) {
                return $b['modified'] - $a['modified'];
            });
        }

        return $files;
    }

    /**
     * Получение содержимого лог-файла
     */
    private function getLogContent($filename)
    {
        if (!$filename || !$this->isValidLogFile($filename)) {
            return null;
        }

        $filePath = $this->logDirectory . '/' . $filename;

        if (!file_exists($filePath)) {
            return null;
        }

        // Читаем содержимое файла
        $content = file_get_contents($filePath);

        // Преобразуем специальные символы для безопасного отображения в HTML
        $content = htmlspecialchars($content);

        // Добавляем подсветку для ключевых слов
        $content = $this->highlightLogContent($content);

        return $content;
    }

    /**
     * Проверка, является ли файл допустимым лог-файлом
     */
    private function isValidLogFile($filename)
    {
        // Проверяем, что имя файла содержит только допустимые символы
        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $filename)) {
            return false;
        }

        // Проверяем, что файл находится в директории логов
        $filePath = $this->logDirectory . '/' . $filename;

        if (!file_exists($filePath) || !is_file($filePath)) {
            return false;
        }

        return true;
    }

    /**
     * Подсветка ключевых слов в содержимом лога
     */
    private function highlightLogContent($content)
    {
        // Подсветка ошибок
        $content = preg_replace('/\b(error|Error|ERROR|Exception|FATAL|CRITICAL)\b/i', '<span class="text-danger">$1</span>', $content);

        // Подсветка предупреждений
        $content = preg_replace('/\b(warning|Warning|WARNING|WARN|Notice)\b/i', '<span class="text-warning">$1</span>', $content);

        // Подсветка успешных событий
        $content = preg_replace('/\b(success|Success|SUCCESS|OK|DONE)\b/i', '<span class="text-success">$1</span>', $content);

        // Подсветка информационных сообщений
        $content = preg_replace('/\b(info|Info|INFO|DEBUG)\b/i', '<span class="text-info">$1</span>', $content);

        // Подсветка дат и времени
        $content = preg_replace('/\[([0-9-]+ [0-9:]+)\]/', '<span class="text-primary">[$1]</span>', $content);

        // Преобразуем переносы строк в HTML
        $content = nl2br($content);

        return $content;
    }
}