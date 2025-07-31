/**
 * Dashboard JavaScript
 * Скрипты для страницы панели управления
 */

// Функция для обновления данных дашборда
function refreshDashboard() {
    // Показываем индикатор загрузки
    showLoadingOverlay();
    
    // Перезагружаем страницу
    location.reload();
}

// Функция для отображения индикатора загрузки
function showLoadingOverlay() {
    // Создаем элемент overlay, если его еще нет
    if (!document.getElementById('loading-overlay')) {
        const overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.style.position = 'fixed';
        overlay.style.top = '0';
        overlay.style.left = '0';
        overlay.style.width = '100%';
        overlay.style.height = '100%';
        overlay.style.backgroundColor = 'rgba(255, 255, 255, 0.8)';
        overlay.style.display = 'flex';
        overlay.style.justifyContent = 'center';
        overlay.style.alignItems = 'center';
        overlay.style.zIndex = '9999';
        
        const spinner = document.createElement('div');
        spinner.className = 'spinner-border text-primary';
        spinner.setAttribute('role', 'status');
        
        const span = document.createElement('span');
        span.className = 'visually-hidden';
        span.textContent = 'Загрузка...';
        
        spinner.appendChild(span);
        overlay.appendChild(spinner);
        document.body.appendChild(overlay);
    } else {
        document.getElementById('loading-overlay').style.display = 'flex';
    }
    
    // Скрываем overlay после загрузки или через 10 секунд
    setTimeout(() => {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }, 10000);
}

// Инициализация графиков и таблиц при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация DataTables для всех таблиц с классом datatable
    if (typeof $.fn.DataTable !== 'undefined') {
        $('.datatable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json'
            },
            pageLength: 5,
            lengthMenu: [5, 10, 25, 50],
            responsive: true,
            dom: 'rtip' // Убираем поиск и выбор количества записей для компактности
        });
    }
    
    // Настраиваем автоматическое обновление данных каждые 5 минут
    setTimeout(refreshDashboard, 5 * 60 * 1000);
});