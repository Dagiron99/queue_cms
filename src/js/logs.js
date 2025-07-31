/**
 * Logs JavaScript
 * Скрипты для страницы логов
 */

// Функция для обновления страницы логов
function refreshLogs() {
    // Показываем индикатор загрузки
    showLoadingOverlay();
    
    // Перезагружаем страницу
    location.reload();
}

// Функция поиска в логах
function searchLogs() {
    const searchInput = document.getElementById('search-logs');
    const searchTerm = searchInput.value.toLowerCase();
    const logContainer = document.getElementById('log-content');
    
    if (!searchTerm || !logContainer) return;
    
    const logContent = logContainer.innerHTML;
    
    // Очищаем предыдущие результаты поиска
    logContainer.innerHTML = logContent.replace(/<mark class="highlight">(.*?)<\/mark>/gi, '$1');
    
    if (searchTerm.length < 2) return; // Не ищем слишком короткие строки
    
    // Создаем регулярное выражение для поиска
    const regex = new RegExp(`(${escapeRegExp(searchTerm)})`, 'gi');
    
    // Заменяем найденный текст с выделением
    logContainer.innerHTML = logContainer.innerHTML.replace(regex, '<mark class="highlight">$1</mark>');
    
    // Прокручиваем к первому результату
    const firstResult = logContainer.querySelector('.highlight');
    if (firstResult) {
        firstResult.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// Функция экранирования специальных символов в регулярных выражениях
function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Настройка кнопок прокрутки
    const btnScrollBottom = document.getElementById('btn-scroll-bottom');
    if (btnScrollBottom) {
        btnScrollBottom.addEventListener('click', function() {
            const logContent = document.getElementById('log-content');
            if (logContent) {
                logContent.scrollTop = logContent.scrollHeight;
            }
        });
    }
    
    const btnScrollTop = document.getElementById('btn-scroll-top');
    if (btnScrollTop) {
        btnScrollTop.addEventListener('click', function() {
            const logContent = document.getElementById('log-content');
            if (logContent) {
                logContent.scrollTop = 0;
            }
        });
    }
    
    // Настройка поиска
    const searchInput = document.getElementById('search-logs');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(searchLogs, 300));
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                searchLogs();
            }
        });
    }
    
    // Настройка фильтров
    const filterButtons = document.querySelectorAll('[data-filter]');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filterType = this.getAttribute('data-filter');
            filterLogEntries(filterType);
        });
    });
    
    // Автоматическая прокрутка вниз при загрузке страницы
    const logContent = document.getElementById('log-content');
    if (logContent) {
        logContent.scrollTop = logContent.scrollHeight;
    }
});

// Функция для фильтрации записей лога
function filterLogEntries(filterType) {
    const logContent = document.getElementById('log-content');
    if (!logContent) return;
    
    // Сбрасываем все фильтры
    if (filterType === 'reset') {
        const allLines = logContent.querySelectorAll('.log-line');
        allLines.forEach(line => {
            line.style.display = '';
        });
        return;
    }
    
    // Скрываем все строки
    const allLines = logContent.querySelectorAll('.log-line');
    allLines.forEach(line => {
        line.style.display = 'none';
    });
    
    // Показываем строки с нужным фильтром
    const filteredLines = logContent.querySelectorAll(`.log-line[data-level="${filterType}"]`);
    filteredLines.forEach(line => {
        line.style.display = '';
    });
}

// Функция debounce для предотвращения слишком частых вызовов функции
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this, args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            func.apply(context, args);
        }, wait);
    };
}