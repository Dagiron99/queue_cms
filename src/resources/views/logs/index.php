<div class="row mb-4">
    <div class="col-md-6">
        <div class="d-flex align-items-center">
            <i class="bi bi-journal-text fs-1 me-3 text-primary"></i>
            <div>
                <h5 class="mb-0">Журнал системных событий</h5>
                <p class="text-muted mb-0">Просмотр и анализ логов системы</p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="d-flex justify-content-end">
            <?php if ($selectedLog): ?>
                <form method="post" action="/logs/clear" class="me-2" onsubmit="return confirm('Вы уверены, что хотите очистить лог-файл?');">

                    <input type="hidden" name="file" value="<?php echo htmlspecialchars($selectedLog); ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash me-1"></i> Очистить лог
                    </button>
                </form>
                <a href="/logs" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="bi bi-arrow-left me-1"></i> Назад к списку
                </a>
            <?php endif; ?>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="refreshLogs()">
                <i class="bi bi-arrow-clockwise me-1"></i> Обновить
            </button>
        </div>
    </div>
</div>

<?php if (empty($logFiles)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i> Лог-файлы не найдены. Возможно, система еще не создала файлы логов.
    </div>
<?php elseif (!$selectedLog): ?>
    <!-- Список лог-файлов -->
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <i class="bi bi-file-text me-2"></i>
            <span>Доступные лог-файлы</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Имя файла</th>
                            <th>Размер</th>
                            <th>Дата изменения</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logFiles as $log): ?>
                            <tr>
                                <td>
                                    <i class="bi bi-file-text me-2"></i>
                                    <?php echo htmlspecialchars($log['name']); ?>
                                </td>
                                <td><?php echo $this->formatFileSize($log['size']); ?></td>
                                <td><?php echo date('d.m.Y H:i:s', $log['modified']); ?></td>
                                <td>
                                    <a href="/logs?file=<?php echo urlencode($log['name']); ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye me-1"></i> Просмотр
                                    </a>
                                    <form method="post" action="/logs/clear" class="d-inline" onsubmit="return confirm('Вы уверены, что хотите очистить лог-файл?');">
                                        <input type="hidden" name="file" value="<?php echo htmlspecialchars($log['name']); ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash me-1"></i> Очистить
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Содержимое выбранного лог-файла -->
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <i class="bi bi-file-text me-2"></i>
            <span>Содержимое файла: <?php echo htmlspecialchars($selectedLog); ?></span>
        </div>
        <div class="card-body">
            <div class="log-controls mb-3">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-scroll-bottom">
                        <i class="bi bi-arrow-down"></i> Прокрутить вниз
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-scroll-top">
                        <i class="bi bi-arrow-up"></i> Прокрутить вверх
                    </button>
                </div>
                <div class="btn-group ms-2" role="group">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-filter-errors">
                        <i class="bi bi-exclamation-triangle"></i> Только ошибки
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-filter-reset">
                        <i class="bi bi-funnel"></i> Сбросить фильтры
                    </button>
                </div>
            </div>
            
            <?php if (empty($logContent)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i> Лог-файл пуст.
                </div>
            <?php else: ?>
                <div class="log-container" id="log-content">
                    <pre><?php echo $logContent; ?></pre>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Прокрутка до нижней части лога
    const btnScrollBottom = document.getElementById('btn-scroll-bottom');
    if (btnScrollBottom) {
        btnScrollBottom.addEventListener('click', function() {
            const logContent = document.getElementById('log-content');
            if (logContent) {
                logContent.scrollTop = logContent.scrollHeight;
            }
        });
    }
    
    // Прокрутка до верхней части лога
    const btnScrollTop = document.getElementById('btn-scroll-top');
    if (btnScrollTop) {
        btnScrollTop.addEventListener('click', function() {
            const logContent = document.getElementById('log-content');
            if (logContent) {
                logContent.scrollTop = 0;
            }
        });
    }
    
    // Фильтрация только ошибок
    const btnFilterErrors = document.getElementById('btn-filter-errors');
    if (btnFilterErrors) {
        btnFilterErrors.addEventListener('click', function() {
            const logContent = document.getElementById('log-content');
            if (logContent) {
                const lines = logContent.querySelectorAll('pre span.text-danger');
                
                // Скрываем все строки
                const allLines = logContent.querySelectorAll('pre br');
                allLines.forEach(line => {
                    line.parentNode.style.display = 'none';
                });
                
                // Показываем только строки с ошибками
                lines.forEach(line => {
                    let currentNode = line;
                    while (currentNode && currentNode.tagName !== 'PRE') {
                        currentNode = currentNode.parentNode;
                    }
                    if (currentNode) {
                        currentNode.style.display = 'block';
                    }
                });
            }
        });
    }
    
    // Сброс фильтров
    const btnFilterReset = document.getElementById('btn-filter-reset');
    if (btnFilterReset) {
        btnFilterReset.addEventListener('click', function() {
            const logContent = document.getElementById('log-content');
            if (logContent) {
                const allLines = logContent.querySelectorAll('pre');
                allLines.forEach(line => {
                    line.style.display = 'block';
                });
            }
        });
    }
    
    // Автоматическая прокрутка вниз при загрузке страницы
    const logContent = document.getElementById('log-content');
    if (logContent) {
        logContent.scrollTop = logContent.scrollHeight;
    }
});

// Функция обновления страницы логов
function refreshLogs() {
    location.reload();
}
</script>

<style>
.log-container {
    background-color: #272822;
    color: #f8f8f2;
    padding: 15px;
    border-radius: 5px;
    max-height: 600px;
    overflow-y: auto;
    font-family: 'Courier New', Courier, monospace;
    font-size: 0.9rem;
    line-height: 1.5;
}

.log-container pre {
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
    color: #f8f8f2;
}

.log-controls {
    position: sticky;
    top: 0;
    z-index: 10;
}
</style>