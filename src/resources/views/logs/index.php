<?php
// Заголовок страницы
$pageTitle = 'Логи распределения заказов';

// Начало буфера вывода
ob_start();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/dashboard">Главная</a></li>
                    <li class="breadcrumb-item active">Логи распределения заказов</li>
                </ol>
            </nav>
            
            <h1>Логи распределения заказов</h1>
            
            <?php 
            if (function_exists('displayFlashMessages')) {
                displayFlashMessages(); 
            } else {
                echo '<div class="alert alert-warning">Flash messages function is not available.</div>';
            }
            ?>
            
            <!-- Фильтры -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Фильтры</h5>
                </div>
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-3">
                            <label for="queue_id" class="form-label">Очередь</label>
                            <select class="form-select" id="queue_id" name="queue_id">
                                <option value="">Все очереди</option>
                                <?php foreach ($queues as $queue): ?>
                                    <option value="<?php echo $queue['id']; ?>" <?php echo (isset($filter['queue_id']) && $filter['queue_id'] == $queue['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($queue['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="manager_id" class="form-label">Менеджер</label>
                            <select class="form-select" id="manager_id" name="manager_id">
                                <option value="">Все менеджеры</option>
                                <?php foreach ($managers as $manager): ?>
                                    <option value="<?php echo $manager['id']; ?>" <?php echo (isset($filter['manager_id']) && $filter['manager_id'] == $manager['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($manager['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="status" class="form-label">Статус</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Все статусы</option>
                                <option value="success" <?php echo (isset($filter['status']) && $filter['status'] == 'success') ? 'selected' : ''; ?>>Успешно</option>
                                <option value="error" <?php echo (isset($filter['status']) && $filter['status'] == 'error') ? 'selected' : ''; ?>>Ошибка</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">Дата от</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $filter['date_from'] ?? ''; ?>">
                        </div>
                        
                        <div class="col-md-2">
                            <label for="date_to" class="form-label">Дата до</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $filter['date_to'] ?? ''; ?>">
                        </div>
                        
                        <div class="col-md-8">
                            <label for="search" class="form-label">Поиск по ID заказа или описанию</label>
                            <input type="text" class="form-control" id="search" name="search" value="<?php echo $filter['search'] ?? ''; ?>" placeholder="Введите ID заказа или часть описания">
                        </div>
                        
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Применить фильтры</button>
                            <a href="/logs" class="btn btn-secondary me-2">Сбросить</a>
                            <a href="/logs/export-csv<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>" class="btn btn-success">
                                <i class="bi bi-download"></i> Экспорт в CSV
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Таблица логов -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($logs)): ?>
                        <div class="alert alert-info">
                            Логов не найдено. Попробуйте изменить параметры фильтрации.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Очередь</th>
                                        <th>Менеджер</th>
                                        <th>ID заказа</th>
                                        <th>Статус</th>
                                        <th>Описание</th>
                                        <th>Дата</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?php echo $log['id']; ?></td>
                                            <td><?php echo htmlspecialchars($log['queue_name'] ?? 'Не указано'); ?></td>
                                            <td><?php echo htmlspecialchars($log['manager_name'] ?? 'Не указано'); ?></td>
                                            <td><?php echo htmlspecialchars($log['order_id']); ?></td>
                                            <td>
                                                <?php if ($log['status'] == 'success'): ?>
                                                    <span class="badge bg-success">Успешно</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Ошибка</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['description']); ?></td>
                                            <td><?php echo $log['created_at']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Пагинация -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Навигация по страницам" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php
                                    // Предыдущая страница
                                    if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" aria-label="Предыдущая">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">&laquo;</span>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    // Страницы
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    
                                    // Показываем первую страницу если текущая далеко
                                    if ($startPage > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                                        </li>
                                        <?php if ($startPage > 2): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php
                                    // Показываем последнюю страницу если текущая далеко
                                    if ($endPage < $totalPages): ?>
                                        <?php if ($endPage < $totalPages - 1): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>"><?php echo $totalPages; ?></a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    // Следующая страница
                                    if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" aria-label="Следующая">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">&raquo;</span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/default.php';
?>