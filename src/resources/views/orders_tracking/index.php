

<div class="row mb-4">
    <div class="col-md-6">
        <div class="d-flex align-items-center">
            <i class="bi bi-cart-check fs-1 me-3 text-primary"></i>
            <div>
                <h5 class="mb-0">Отслеживание заказов</h5>
                <p class="text-muted mb-0">Мониторинг и управление заказами в системе</p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="refreshOrderList()">
                <i class="bi bi-arrow-clockwise me-1"></i> Обновить
            </button>
        </div>
    </div>
</div>

<!-- Статистика -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary mb-3">
            <div class="card-header d-flex align-items-center">
                <i class="bi bi-cart me-2"></i>
                <span>Всего заказов</span>
            </div>
            <div class="card-body">
                <h2 class="card-title"><?php echo $statistics['total_orders']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning mb-3">
            <div class="card-header d-flex align-items-center">
                <i class="bi bi-hourglass-split me-2"></i>
                <span>В обработке</span>
            </div>
            <div class="card-body">
                <h2 class="card-title"><?php echo $statistics['in_progress']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success mb-3">
            <div class="card-header d-flex align-items-center">
                <i class="bi bi-check-circle me-2"></i>
                <span>Выполнено</span>
            </div>
            <div class="card-body">
                <h2 class="card-title"><?php echo $statistics['processed']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-danger mb-3">
            <div class="card-header d-flex align-items-center">
                <i class="bi bi-x-circle me-2"></i>
                <span>Отменено</span>
            </div>
            <div class="card-body">
                <h2 class="card-title"><?php echo $statistics['failed']; ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Фильтры -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center">
        <i class="bi bi-funnel me-2"></i>
        <span>Фильтры</span>
    </div>
    <div class="card-body">
        <form id="filter-form" method="get" class="row g-3">
            <div class="col-md-2">
                <label for="status" class="form-label">Статус</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Все статусы</option>
                    <option value="new" <?php echo $status === 'new' ? 'selected' : ''; ?>>Новый</option>
                    <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>В обработке</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Выполнен</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                    <option value="error" <?php echo $status === 'error' ? 'selected' : ''; ?>>Ошибка</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="manager_id" class="form-label">Менеджер</label>
                <select class="form-select" id="manager_id" name="manager_id">
                    <option value="">Все менеджеры</option>
                    <?php foreach ($managers as $manager): ?>
                        <option value="<?php echo $manager['id']; ?>" <?php echo $managerId == $manager['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($manager['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="queue_id" class="form-label">Очередь</label>
                <select class="form-select" id="queue_id" name="queue_id">
                    <option value="">Все очереди</option>
                    <?php foreach ($queues as $queue): ?>
                        <option value="<?php echo $queue['id']; ?>" <?php echo $queueId == $queue['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($queue['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="date_from" class="form-label">Дата с</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $dateFrom; ?>">
            </div>
            <div class="col-md-2">
                <label for="date_to" class="form-label">Дата по</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $dateTo; ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="d-grid gap-2 w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i> Применить
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                        <i class="bi bi-x-circle me-1"></i> Сбросить
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Таблица заказов -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center">
        <i class="bi bi-table me-2"></i>
        <span>Список заказов</span>
    </div>
    <div class="card-body">
        <?php if (empty($orders)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i> Заказы не найдены. Попробуйте изменить параметры фильтрации.
            </div>
        <?php else: ?>
            <div class="table-responsive">
            <table class="table table-striped table-hover datatable">
    <thead>
        <tr>
            <th>ID заказа</th>
            <th>Менеджер</th>
            <th>Начальный статус</th>
            <th>Текущий статус</th>
            <th>Дата назначения</th>
            <th>Последняя проверка</th>
            <th>Обработан</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                <td><?php echo htmlspecialchars($order['manager_name'] ?? 'ID: '.$order['manager_id']); ?></td>
                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($order['initial_status']); ?></span></td>
                <td>
                    <?php
                    $statusClass = 'secondary';
                    $statusText = $order['current_status']; // Используем current_status
                    
                    switch ($order['current_status']) { // Используем current_status
                        case 'new': $statusClass = 'info'; $statusText = 'Новый'; break;
                        case 'processing': $statusClass = 'warning'; $statusText = 'В обработке'; break;
                        case 'completed': case 'done': $statusClass = 'success'; $statusText = 'Выполнен'; break;
                        case 'cancelled': $statusClass = 'danger'; $statusText = 'Отменен'; break;
                    }
                    ?>
                    <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                </td>
                <td><?php echo date('d.m.Y H:i:s', strtotime($order['assigned_at'])); ?></td>
                <td><?php echo date('d.m.Y H:i:s', strtotime($order['last_checked_at'])); ?></td>
                <td>
                    <?php if ($order['processed']): ?>
                        <span class="badge bg-success">Да</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Нет</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-primary" 
                                onclick="showOrderDetails('<?php echo $order['order_id']; ?>')">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-warning" 
                                onclick="showStatusModal('<?php echo $order['order_id']; ?>', '<?php echo $order['current_status']; ?>')">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Модальное окно деталей заказа -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderDetailsModalLabel">Детали заказа</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="order-details-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                    <p class="mt-2">Загрузка данных заказа...</p>
                </div>
                <div id="order-details-content" style="display: none;">
                    <ul class="nav nav-tabs" id="orderDetailsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">Общая информация</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab" aria-controls="history" aria-selected="false">История статусов</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="data-tab" data-bs-toggle="tab" data-bs-target="#data" type="button" role="tab" aria-controls="data" aria-selected="false">Данные заказа</button>
                        </li>
                    </ul>
                    <div class="tab-content p-3 border border-top-0 rounded-bottom" id="orderDetailsTabsContent">
                        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <th>ID заказа:</th>
                                                <td id="order-id"></td>
                                            </tr>
                                            <tr>
                                                <th>Платформа:</th>
                                                <td id="order-platform"></td>
                                            </tr>
                                            <tr>
                                                <th>Статус:</th>
                                                <td id="order-status"></td>
                                            </tr>
                                            <tr>
                                                <th>Дата создания:</th>
                                                <td id="order-created"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <th>Менеджер:</th>
                                                <td id="order-manager"></td>
                                            </tr>
                                            <tr>
                                                <th>Очередь:</th>
                                                <td id="order-queue"></td>
                                            </tr>
                                            <tr>
                                                <th>Последнее обновление:</th>
                                                <td id="order-updated"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
                            <div id="status-history-container">
                                <div class="timeline" id="status-timeline">
                                    <!-- История статусов будет добавлена динамически -->
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="data" role="tabpanel" aria-labelledby="data-tab">
                            <pre id="order-data" class="p-3 bg-light rounded"></pre>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" id="change-status-btn">Изменить статус</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно изменения статуса -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeStatusModalLabel">Изменение статуса заказа</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="change-status-form">
                    <input type="hidden" id="status-order-id" name="order_id">
                    
                    <div class="mb-3">
                        <label for="status-current" class="form-label">Текущий статус</label>
                        <input type="text" class="form-control" id="status-current" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status-new" class="form-label">Новый статус</label>
                        <select class="form-select" id="status-new" name="status" required>
                            <option value="new">Новый</option>
                            <option value="processing">В обработке</option>
                            <option value="completed">Выполнен</option>
                            <option value="cancelled">Отменен</option>
                            <option value="error">Ошибка</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status-comment" class="form-label">Комментарий</label>
                        <textarea class="form-control" id="status-comment" name="comment" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="save-status-btn">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<script>
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
    
    // Устанавливаем обработчик для кнопки сохранения
    document.getElementById('save-status-btn').onclick = saveStatus;
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
    // Инициализация DataTables
    if (typeof $.fn.DataTable !== 'undefined') {
        $('.datatable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json'
            },
            pageLength: 25,
            order: [[5, 'desc']] // Сортировка по дате создания по умолчанию
        });
    }
});
</script>

<style>
/* Стили для временной шкалы (timeline) */
.timeline {
    position: relative;
    padding: 20px 0;
    list-style: none;
    max-width: 1200px;
    margin: 0 auto;
}

.timeline:before {
    content: " ";
    position: absolute;
    top: 0;
    bottom: 0;
    left: 20px;
    width: 3px;
    background-color: #eeeeee;
    margin-left: -1.5px;
}

.timeline-item {
    margin-bottom: 20px;
    position: relative;
}

.timeline-badge {
    width: 40px;
    height: 40px;
    line-height: 40px;
    text-align: center;
    position: absolute;
    top: 0;
    left: 0;
    margin-left: -20px;
    border-radius: 50%;
    z-index: 100;
    color: #fff;
}

.timeline-badge i {
    font-size: 1.4rem;
}

.timeline-content {
    position: relative;
    margin-left: 60px;
    background-color: #f8f9fa;
    border-radius: 6px;
    padding: 15px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.timeline-heading {
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.timeline-body {
    padding-top: 10px;
    border-top: 1px solid #eee;
}
</style>