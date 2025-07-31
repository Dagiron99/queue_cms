<div class="card">
    <div class="card-header">
        <h5>Статистика распределения заказов</h5>
    </div>
    <div class="card-body">
        <!-- Фильтры -->
        <form id="statsFilterForm" class="row g-3 mb-4">
            <div class="col-md-3">
                <label for="queue_filter" class="form-label">Очередь</label>
                <select class="form-control" id="queue_filter" name="queue_id">
                    <option value="">Все очереди</option>
                    <?php foreach ($queues as $queue): ?>
                        <option value="<?= $queue['id'] ?>"><?= $queue['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="date_from" class="form-label">Дата с</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?= date('Y-m-d', strtotime('-7 days')) ?>">
            </div>
            
            <div class="col-md-3">
                <label for="date_to" class="form-label">Дата по</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?= date('Y-m-d') ?>">
            </div>
            
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Применить фильтры</button>
            </div>
        </form>
        
        <!-- Графики распределения -->
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        Распределение по алгоритмам
                    </div>
                    <div class="card-body">
                        <canvas id="algorithmChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        Распределение по менеджерам
                    </div>
                    <div class="card-body">
                        <canvas id="managersChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Детальная статистика по менеджерам -->
        <div class="card">
            <div class="card-header">
                Статистика менеджеров
            </div>
            <div class="card-body">
                <table class="table table-striped" id="managersStatsTable">
                    <thead>
                        <tr>
                            <th>Менеджер</th>
                            <th>Всего заказов</th>
                            <th>В работе</th>
                            <th>Завершено</th>
                            <th>Отменено</th>
                            <th>Эффективность</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Данные будут загружены через AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация графиков
    const algorithmCtx = document.getElementById('algorithmChart').getContext('2d');
    const managersCtx = document.getElementById('managersChart').getContext('2d');
    
    let algorithmChart = new Chart(algorithmCtx, {
        type: 'pie',
        data: {
            labels: ['Round Robin', 'Load Balanced', 'Priority Based'],
            datasets: [{
                data: [0, 0, 0],
                backgroundColor: ['#4e73df', '#1cc88a', '#f6c23e']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    
    let managersChart = new Chart(managersCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Количество заказов',
                data: [],
                backgroundColor: '#36b9cc'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Функция загрузки данных
    function loadStatistics() {
        const formData = new FormData(document.getElementById('statsFilterForm'));
        const params = new URLSearchParams(formData);
        
        fetch('/dashboard/distribution-stats-data?' + params.toString())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Обновляем график алгоритмов
                    algorithmChart.data.datasets[0].data = [
                        data.algorithm_stats.round_robin || 0,
                        data.algorithm_stats.load_balanced || 0,
                        data.algorithm_stats.priority_based || 0
                    ];
                    algorithmChart.update();
                    
                    // Обновляем график менеджеров
                    managersChart.data.labels = data.manager_stats.map(m => m.name);
                    managersChart.data.datasets[0].data = data.manager_stats.map(m => m.total_orders);
                    managersChart.update();
                    
                    // Обновляем таблицу статистики менеджеров
                    const tableBody = document.querySelector('#managersStatsTable tbody');
                    tableBody.innerHTML = '';
                    
                    data.manager_stats.forEach(manager => {
                        const efficiency = manager.total_orders > 0 
                            ? Math.round((manager.completed / manager.total_orders) * 100)
                            : 0;
                            
                        tableBody.innerHTML += `
                            <tr>
                                <td>${manager.name}</td>
                                <td>${manager.total_orders}</td>
                                <td>${manager.in_progress}</td>
                                <td>${manager.completed}</td>
                                <td>${manager.cancelled}</td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar bg-${efficiency >= 70 ? 'success' : efficiency >= 40 ? 'warning' : 'danger'}" 
                                             role="progressbar" style="width: ${efficiency}%" 
                                             aria-valuenow="${efficiency}" aria-valuemin="0" aria-valuemax="100">
                                            ${efficiency}%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                }
            });
    }
    
    // Загружаем статистику при загрузке страницы
    loadStatistics();
    
    // Обработчик формы фильтров
    document.getElementById('statsFilterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        loadStatistics();
    });
});
</script>