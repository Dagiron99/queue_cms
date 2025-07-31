/**
 * OrderTracking JavaScript
 * Скрипты для страницы отслеживания заказов
 */

// Функция обновления списка заказов
function refreshOrderList() {
    location.reload();
}

// Функция сброса фильтров
function resetFilters() {
    document.getElementById('status').value = '';
    document.getElementById('manager_id').value = '';
    document.getElementById('queue_id').value = '';
    document.getElementById('date_from').value = '';
    document.getElementById('date_to').value = '';
    
    // Отправляем форму
    document.getElementById('filter-form').submit();
}

// Показать детали заказа
function showOrderDetails(orderId) {
    // Отображаем модальное окно
    const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    modal.show();
    
    // Показываем индикатор загрузки
    document.getElementById('order-details-loading').style.display = 'block';
    document.getElementById('order-details-content').style.display = 'none';
    
    // Запрашиваем данные заказа
    fetch(`/orders_tracking/get-details?order_id=${encodeURIComponent(orderId)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Заполняем данные на вкладке "Общая информация"
                document.getElementById('order-id').textContent = data.order.order_id;
                document.getElementById('order-platform').textContent = data.order.platform;
                
                // Форматируем статус
                let statusHTML = '';
                switch (data.order.status) {
                    case 'new':
                        statusHTML = '<span class="badge bg-info">Новый</span>';
                        break;
                    case 'processing':
                        statusHTML = '<span class="badge bg-warning">В обработке</span>';
                        break;
                    case 'completed':
                        statusHTML = '<span class="badge bg-success">Выполнен</span>';
                        break;
                    case 'cancelled':
                        statusHTML = '<span class="badge bg-danger">Отменен</span>';
                        break;
                    case 'error':
                        statusHTML = '<span class="badge bg-danger">Ошибка</span>';
                        break;
                    default:
                        statusHTML = `<span class="badge bg-secondary">${data.order.status}</span>`;
                }
                document.getElementById('order-status').innerHTML = statusHTML;
                
                // Форматируем даты
                document.getElementById('order-created').textContent = formatDate(data.order.created_at);
                document.getElementById('order-updated').textContent = data.order.updated_at ? formatDate(data.order.updated_at) : '-';
                
                // Информация о менеджере и очереди
                document.getElementById('order-manager').textContent = data.order.manager_name || 'Не назначен';
                document.getElementById('order-queue').textContent = data.order.queue_name || 'Не определена';
                
                // Заполняем данные на вкладке "Данные заказа"
                if (data.order.data) {
                    let orderData = data.order.data;
                    if (typeof orderData === 'string') {
                        try {
                            orderData = JSON.parse(orderData);
                        } catch (e) {
                            console.error('Error parsing order data:', e);
                        }
                    }
                    document.getElementById('order-data').textContent = JSON.stringify(orderData, null, 2);
                } else {
                    document.getElementById('order-data').textContent = 'Данные отсутствуют';
                }
                
                // Заполняем историю статусов
                const timelineEl = document.getElementById('status-timeline');
                timelineEl.innerHTML = '';
                
                if (data.statusHistory && data.statusHistory.length > 0) {
                    data.statusHistory.forEach(history => {
                        // Определяем цвет для статуса
                        let statusClass = 'secondary';
                        switch (history.status) {
                            case 'new': statusClass = 'info'; break;
                            case 'processing': statusClass = 'warning'; break;
                            case 'completed': statusClass = 'success'; break;
                            case 'cancelled': case 'error': statusClass = 'danger'; break;
                        }
                        
                        // Форматируем данные платформы
                        let platformDataHtml = '';
                        if (history.platform_data) {
                            let platformData = history.platform_data;
                            if (typeof platformData === 'string') {
                                try {
                                    platformData = JSON.parse(platformData);
                                } catch (e) {
                                    console.error('Error parsing platform data:', e);
                                }
                            }
                            
                            if (platformData.comment) {
                                platformDataHtml = `<p class="mb-0 text-muted small">Комментарий: ${platformData.comment}</p>`;
                            }
                        }
                        
                        // Создаем элемент истории
                        const historyItem = document.createElement('div');
                        historyItem.className = 'timeline-item';
                        historyItem.innerHTML = `
                            <div class="timeline-badge bg-${statusClass}">
                                <i class="bi bi-record-circle"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-heading">
                                    <span class="badge bg-${statusClass}">${history.status}</span>
                                    <span class="text-muted">${formatDate(history.created_at)}</span>
                                </div>
                                <div class="timeline-body">
                                    ${platformDataHtml}
                                </div>
                            </div>
                        `;
                        
                        timelineEl.appendChild(historyItem);
                    });
                } else {
                    timelineEl.innerHTML = '<p class="text-center text-muted">История статусов отсутствует</p>';
                }
                
                // Устанавливаем обработчик для кнопки изменения статуса
                document.getElementById('change-status-btn').onclick = function() {
                    showStatusModal(data.order.order_id, data.order.status);
                };
                
                // Скрываем индикатор загрузки и показываем контент
                document.getElementById('order-details-loading').style.display = 'none';
                document.getElementById('order-details-content').style.display = 'block';
            } else {
                alert(data.message || 'Произошла ошибка при загрузке данных заказа');
            }
        })
        .catch(error => {
            console.error('Error fetching order details:', error);
            alert('Произошла ошибка при загрузке данных заказа');
        });
}


// Показать модальное окно изменения статуса
function showStatusModal(orderId, currentStatus) {
    // Заполняем поля формы
    document.getElementById('status-order-id').value = orderId;
    document.getElementById('status-current').value = formatStatus(currentStatus);
    document.getElementById('status-new').value = currentStatus;
    document.getElementById('status-comment').value = '';
    
    // Отображаем модальное окно
    const modal = new bootstrap.Modal(document.getElementById('changeStatusModal'));
    modal.show();
}

// Сохранение изменения статуса
function saveStatus() {
    const form = document.getElementById('change-status-form');
    const formData = new FormData(form);
    
    fetch('/orders_tracking/update-status', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Закрываем модальное окно
        const modal = bootstrap.Modal.getInstance(document.getElementById('changeStatusModal'));
        modal.hide();
        
        if (data.success) {
            // Показываем сообщение об успешном изменении
            alert('Статус успешно изменен');
            // Обновляем страницу для отображения изменений
            location.reload();
        } else {
            alert(data.message || 'Произошла ошибка при изменении статуса');
        }
    })
    .catch(error => {
        console.error('Error updating status:', error);
        alert('Произошла ошибка при изменении статуса');
    });
}

// Форматирование даты
function formatDate(dateString) {
    if (!dateString) return '-';
    
    const date = new Date(dateString);
    return date.toLocaleString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
}

// Форматирование статуса
function formatStatus(status) {
    switch (status) {
        case 'new': return 'Новый';
        case 'processing': return 'В обработке';
        case 'completed': return 'Выполнен';
        case 'cancelled': return 'Отменен';
        case 'error': return 'Ошибка';
        default: return status;
    }
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Проверка наличия DataTables
    if (typeof $.fn.DataTable !== 'undefined') {
        // Проверка, не инициализирована ли таблица уже
        if (!$.fn.dataTable.isDataTable('.datatable')) {
            $('.datatable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json'
                },
                pageLength: 25,
                order: [[4, 'desc']] // Используем колонку 4 (Дата назначения) для сортировки
            });
        }
    }
});