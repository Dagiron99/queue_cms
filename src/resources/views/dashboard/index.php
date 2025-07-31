<?php
/**
 * Панель управления — рабочий шаблон.
 * Адаптирован для работы с таблицей orders_tracking
 */
?>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="d-flex align-items-center">
            <i class="bi bi-speedometer2 fs-1 me-3 text-primary"></i>
            <div>
                <h5 class="mb-0">Панель управления</h5>
                <p class="text-muted mb-0">Обзор системы распределения заказов</p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="d-flex justify-content-end align-items-center">
            <span class="me-3">Текущая дата: <?php echo date('d.m.Y'); ?></span>
            <button type="button" class="btn btn-primary" id="refreshStats">
                <i class="bi bi-arrow-repeat me-2"></i>Обновить статистику
            </button>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Карточка с активными менеджерами -->
    <div class="col-md-3 mb-4">
        <div class="card h-100 border-left-primary">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col-auto">
                        <i class="bi bi-person-check fs-1 text-primary"></i>
                    </div>
                    <div class="col ms-3">
                        <div class="text-xs text-muted mb-1">Активные менеджеры</div>
                        <div class="h5 mb-0 font-weight-bold">
                            <?php echo isset($activeManagers) ? count($activeManagers) : 0; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent">
                <a href="/managers" class="text-primary text-decoration-none">
                    Подробнее <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Карточка с неактивными менеджерами -->
    <div class="col-md-3 mb-4">
        <div class="card h-100 border-left-warning">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col-auto">
                        <i class="bi bi-person-dash fs-1 text-warning"></i>
                    </div>
                    <div class="col ms-3">
                        <div class="text-xs text-muted mb-1">Неактивные менеджеры</div>
                        <div class="h5 mb-0 font-weight-bold">
                            <?php echo isset($inactiveManagers) ? count($inactiveManagers) : 0; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent">
                <a href="/managers" class="text-warning text-decoration-none">
                    Подробнее <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Карточка с количеством очередей (отображаем только если есть данные) -->
    <?php if (!empty($queues)): ?>
        <div class="col-md-3 mb-4">
            <div class="card h-100 border-left-info">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col-auto">
                            <i class="bi bi-list-ol fs-1 text-info"></i>
                        </div>
                        <div class="col ms-3">
                            <div class="text-xs text-muted mb-1">Активные очереди</div>
                            <div class="h5 mb-0 font-weight-bold">
                                <?php
                                $activeQueues = isset($queues) ? array_filter($queues, function ($queue) {
                                    return !empty($queue['is_active']);
                                }) : [];
                                echo count($activeQueues);
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="/queues" class="text-info text-decoration-none">
                        Подробнее <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="col-md-3 mb-4">
            <div class="card h-100 border-left-info">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col-auto">
                            <i class="bi bi-clock-history fs-1 text-info"></i>
                        </div>
                        <div class="col ms-3">
                            <div class="text-xs text-muted mb-1">Средняя обработка</div>
                            <div class="h5 mb-0 font-weight-bold">
                                <?php
                                // Вычисляем среднее время обработки заказов (в часах)
                                echo isset($orderStats['avg_processing_time']) ?
                                    $orderStats['avg_processing_time'] . ' ч' :
                                    'Н/Д';
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="/orders_tracking" class="text-info text-decoration-none">
                        Подробнее <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Карточка с количеством заказов за сегодня -->
    <div class="col-md-3 mb-4">
        <div class="card h-100 border-left-success">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col-auto">
                        <i class="bi bi-cart-check fs-1 text-success"></i>
                    </div>
                    <div class="col ms-3">
                        <div class="text-xs text-muted mb-1">Заказов сегодня</div>
                        <div class="h5 mb-0 font-weight-bold">
                            <?php echo isset($orderStats['today']) ? $orderStats['today'] : 0; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent">
                <a href="/orders_tracking" class="text-success text-decoration-none">
                    Подробнее <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- График распределения заказов -->
    <div class="col-md-8 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-graph-up me-2"></i>Распределение заказов (последние 7 дней)
                </h6>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                        id="chartOptionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-gear-fill"></i>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="chartOptionsDropdown">
                        <li><a class="dropdown-item" href="#" id="viewDaily">По дням</a></li>
                        <li><a class="dropdown-item" href="#" id="viewWeekly">По неделям</a></li>
                        <li><a class="dropdown-item" href="#" id="viewMonthly">По месяцам</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="#" id="downloadChartData">Скачать данные</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <canvas id="distributionChart" height="300" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Распределение нагрузки по менеджерам -->
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-people me-2"></i>Нагрузка менеджеров
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($activeManagers)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Нет активных менеджеров.
                    </div>
                <?php else: ?>
                    <?php foreach ($activeManagers as $manager): ?>
                        <?php
                        $currentLoad = $manager['current_load'] ?? 0;
                        $maxLoad = $manager['max_load'] ?? 10;
                        $loadPercentage = $maxLoad > 0 ? ($currentLoad / $maxLoad) * 100 : 0;
                        $progressClass = 'bg-success';
                        if ($loadPercentage > 75) {
                            $progressClass = 'bg-danger';
                        } elseif ($loadPercentage > 50) {
                            $progressClass = 'bg-warning';
                        }
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><?php echo htmlspecialchars($manager['name']); ?></span>
                                <span><?php echo $currentLoad; ?> / <?php echo $maxLoad; ?></span>
                            </div>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar <?php echo $progressClass; ?>" role="progressbar"
                                    style="width: <?php echo $loadPercentage; ?>%"
                                    aria-valuenow="<?php echo $loadPercentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Последние заказы -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-list-check me-2"></i>Последние заказы
                </h6>
            </div>
            <div class="card-body">
                <?php if (isset($recentOrders) && !empty($recentOrders)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Менеджер</th>
                                    <th>Статус</th>
                                    <th>Дата</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['id'] ?? 'Н/Д'); ?></td>
                                        <td><?php echo htmlspecialchars($order['manager_name'] ?? 'Не назначен'); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = 'badge bg-secondary';
                                            $statusText = $order['status'] ?? 'Неизвестно';

                                            if (isset($order['status'])) {
                                                switch ($order['status']) {
                                                    // существующий код переключателя
                                                }
                                            }

                                            switch ($order['status']) {
                                                case 'new':
                                                    $statusClass = 'badge bg-primary';
                                                    $statusText = 'Новый';
                                                    break;
                                                case 'processing':
                                                    $statusClass = 'badge bg-info';
                                                    $statusText = 'В обработке';
                                                    break;
                                                case 'completed':
                                                case 'done':
                                                    $statusClass = 'badge bg-success';
                                                    $statusText = 'Завершен';
                                                    break;
                                                case 'cancelled':
                                                    $statusClass = 'badge bg-danger';
                                                    $statusText = 'Отменен';
                                                    break;
                                            }
                                            ?>
                                            <span class="<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                        </td>
                                        <td><?php echo isset($order['created_at']) ? date('d.m.Y H:i', strtotime($order['created_at'])) : 'Н/Д'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Нет данных о последних заказах.
                    </div>
                <?php endif; ?>

                <div class="text-center mt-3">
                    <a href="/orders_tracking" class="btn btn-sm btn-outline-primary">Все заказы</a>
                </div>
            </div>
        </div>
    </div>

    <!-- События системы -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-activity me-2"></i>События системы
                </h6>
            </div>
            <div class="card-body">
                <?php if (isset($recentLogs) && !empty($recentLogs)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentLogs as $log): ?>
                            <?php
                            $logIconClass = 'bi-info-circle text-info';
                            switch ($log['level']) {
                                case 'error':
                                    $logIconClass = 'bi-exclamation-triangle text-danger';
                                    break;
                                case 'warning':
                                    $logIconClass = 'bi-exclamation-circle text-warning';
                                    break;
                                case 'success':
                                    $logIconClass = 'bi-check-circle text-success';
                                    break;
                            }
                            ?>
                            <li class="list-group-item border-0 py-2">
                                <div class="d-flex">
                                    <div class="me-3">
                                        <i class="bi <?php echo $logIconClass; ?> fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="small text-muted">
                                            <?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?>
                                        </div>
                                        <div><?php echo htmlspecialchars($log['message']); ?></div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Нет данных о последних событиях.
                    </div>
                <?php endif; ?>

                <div class="text-center mt-3">
                    <a href="/logs" class="btn btn-sm btn-outline-primary">Все события</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Данные для графика
        const distributionData = <?php echo json_encode($distributionStats ?? []); ?>;

        const labels = distributionData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit' });
        });

        const counts = distributionData.map(item => item.count);

        if (typeof Chart !== 'undefined' && document.getElementById('distributionChart') && labels.length > 0) {
            const ctx = document.getElementById('distributionChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Количество распределенных заказов',
                        data: counts,
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                        tension: 0.3
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.9)',
                            titleColor: '#6e707e',
                            bodyColor: '#858796',
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            caretPadding: 10,
                            displayColors: false,
                            callbacks: {
                                label: function (context) {
                                    return `Заказов: ${context.raw}`;
                                }
                            }
                        }
                    }
                }
            });
        } else if (document.getElementById('distributionChart')) {
            // Если нет данных для графика
            const ctx = document.getElementById('distributionChart');
            ctx.parentNode.innerHTML = '<div class="alert alert-info text-center py-5"><i class="bi bi-info-circle me-2"></i>Недостаточно данных для построения графика</div>';
        }

        // Обработчик кнопки обновления статистики
        let refreshBtn = document.getElementById('refreshStats');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () {
                this.disabled = true;
                this.innerHTML = '<i class="bi bi-arrow-repeat me-2 spinning"></i>Обновление...';

                // Реальное обновление данных через AJAX или просто перезагрузка страницы
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            });
        }

        // Обработчики переключения вида графика
        ['viewDaily', 'viewWeekly', 'viewMonthly', 'downloadChartData'].forEach(function (id) {
            let btn = document.getElementById(id);
            if (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (typeof showNotification === 'function') {
                        if (id === 'downloadChartData') {
                            // Скачивание данных в CSV
                            const csvContent = "data:text/csv;charset=utf-8,"
                                + "Дата,Количество\n"
                                + distributionData.map(item => `${item.date},${item.count}`).join("\n");

                            const encodedUri = encodeURI(csvContent);
                            const link = document.createElement("a");
                            link.setAttribute("href", encodedUri);
                            link.setAttribute("download", "order_distribution.csv");
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);

                            showNotification('Данные графика скачаны', 'success');
                        } else {
                            showNotification('Функция в разработке', 'info');
                        }
                    }
                });
            }
        });

        // Функция для показа уведомлений
        window.showNotification = function (message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');

            toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

            // Добавляем контейнер для уведомлений, если его нет
            let toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                document.body.appendChild(toastContainer);
            }

            toastContainer.appendChild(toast);

            const bsToast = new bootstrap.Toast(toast, {
                autohide: true,
                delay: 3000
            });

            bsToast.show();

            // Удаляем элемент после скрытия
            toast.addEventListener('hidden.bs.toast', function () {
                toast.remove();
            });
        };

        // Добавляем стили для вращающейся иконки
        document.head.insertAdjacentHTML('beforeend', `
        <style>
            .spinning {
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        </style>
    `);
    });
</script>