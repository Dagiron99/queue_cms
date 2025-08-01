<?php
// Заголовок страницы
$pageTitle = 'Тестирование алгоритмов распределения';

// Начало буфера вывода
ob_start();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/dashboard">Главная</a></li>
                    <li class="breadcrumb-item active">Тестирование алгоритмов</li>
                </ol>
            </nav>

            <h1>Тестирование алгоритмов распределения заказов</h1>

            <div class="alert alert-info">
                <p>На этой странице вы можете протестировать работу различных алгоритмов распределения заказов.</p>
                <p>Выберите очередь и количество заказов для симуляции распределения.</p>
            </div>

            <!-- Форма тестирования -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Параметры тестирования</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="/tests/distribution" class="row g-3">
                        <div class="col-md-4">
                            <label for="queue_id" class="form-label">Очередь</label>
                            <select class="form-select" id="queue_id" name="queue_id" required>
                                <option value="">-- Выберите очередь --</option>
                                <?php foreach ($queues as $queue): ?>
                                    <option value="<?php echo $queue['id']; ?>" <?php echo (isset($_POST['queue_id']) && $_POST['queue_id'] == $queue['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($queue['name']); ?> (<?php echo $queue['type']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="order_count" class="form-label">Количество заказов</label>
                            <input type="number" class="form-control" id="order_count" name="order_count" min="1"
                                max="100" value="<?php echo $_POST['order_count'] ?? 10; ?>" required>
                        </div>

                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Запустить тест</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!empty($results)): ?>
                <!-- Результаты тестирования -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Результаты распределения заказов</h5>
                    </div>
                    <div class="card-body">
                        <h4>Информация об очереди</h4>
                        <ul>
                            <li><strong>Название очереди:</strong> <?php echo htmlspecialchars($queueInfo['name']); ?></li>
                            <li><strong>Тип (алгоритм):</strong> <?php echo $queueInfo['type']; ?></li>
                            <li><strong>Количество менеджеров:</strong> <?php echo count($managers); ?></li>
                        </ul>

                        <h4>Распределение по менеджерам</h4>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Менеджер</th>
                                        <th>Онлайн</th>
                                        <th>Fallback</th>
                                        <th>Получено заказов</th>
                                        <th>Лимит в очереди</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($managerStats as $managerId => $stats): ?>
                                        <tr>
                                            <td><?php echo $managerId; ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($stats['name'] ?? 'Неизвестно'); ?>
                                            </td>
                                            <td>
                                                <?php if (isset($stats['is_online']) && $stats['is_online']): ?>
                                                    <span class="badge bg-success">Онлайн</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Оффлайн</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($stats['is_fallback']) && $stats['is_fallback']): ?>
                                                    <span class="badge bg-info">Да</span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-dark">Нет</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $stats['orders'] ?? 0; ?></td>
                                            <td>
                                                <?php
                                                // Ищем максимальную нагрузку для менеджера в этой очереди
                                                $maxLoad = 'Неизвестно';
                                                foreach ($managers as $manager) {
                                                    if (isset($manager['id']) && $manager['id'] == $managerId) {
                                                        $maxLoad = $manager['queue_max_load'] ?? 'Неизвестно';
                                                        break;
                                                    }
                                                }
                                                echo $maxLoad;
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <h4>Детали распределения</h4>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Заказ</th>
                                        <th>Менеджер</th>
                                        <th>Статус</th>
                                        <th>Комментарий</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results as $i => $result): ?>
                                        <tr>
                                            <td>Order-<?php echo $i + 1; ?></td>
                                            <td>
                                                <?php if (isset($result['manager_name'])): ?>
                                                    <?php echo htmlspecialchars($result['manager_name']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Не назначен</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($result === false): ?>
                                                    <span class="badge bg-danger">Ошибка</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Успех</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($result['error'])): ?>
                                                    <span
                                                        class="text-danger"><?php echo htmlspecialchars($result['error']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-success">Заказ успешно распределен</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/default.php';
?>