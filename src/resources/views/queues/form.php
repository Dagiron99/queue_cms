<div class="card">
    <div class="card-header">
        <?= isset($queue) ? 'Редактирование очереди' : 'Создание новой очереди' ?>
    </div>
    <div class="card-body">
        <form method="post" action="<?= isset($queue) ? '/queues/update/' . $queue['id'] : '/queues/store' ?>">
            
            <div class="form-group mb-3">
                <label for="name">Название очереди</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= isset($queue) ? $queue['name'] : '' ?>" required>
            </div>
            
            <div class="form-group mb-3">
                <label for="type">Тип очереди</label>
                <select class="form-control" id="type" name="type" required>
                    <option value="online" <?= (isset($queue) && $queue['type'] == 'online') ? 'selected' : '' ?>>Онлайн заказы</option>
                    <option value="force" <?= (isset($queue) && $queue['type'] == 'force') ? 'selected' : '' ?>>Срочные заказы</option>
                    <option value="online-wellback" <?= (isset($queue) && $queue['type'] == 'online-wellback') ? 'selected' : '' ?>>Обратный звонок</option>
                </select>
                <small class="form-text text-muted">Определяет, как заказы будут автоматически распределяться в очереди.</small>
            </div>
            
            <!-- Добавляем выбор алгоритма распределения -->
            <div class="form-group mb-3">
                <label for="algorithm">Алгоритм распределения</label>
                <select class="form-control" id="algorithm" name="algorithm" required>
                    <option value="round_robin" <?= (isset($queue) && $queue['algorithm'] == 'round_robin') ? 'selected' : '' ?>>Round Robin (по очереди)</option>
                    <option value="load_balanced" <?= (isset($queue) && $queue['algorithm'] == 'load_balanced') ? 'selected' : '' ?>>Load Balanced (по нагрузке)</option>
                    <option value="priority_based" <?= (isset($queue) && $queue['algorithm'] == 'priority_based') ? 'selected' : '' ?>>Priority Based (по приоритету)</option>
                </select>
                <small class="form-text text-muted">
                    <ul>
                        <li><strong>Round Robin</strong> - заказы распределяются по очереди между всеми менеджерами</li>
                        <li><strong>Load Balanced</strong> - заказы направляются менеджерам с наименьшей текущей нагрузкой</li>
                        <li><strong>Priority Based</strong> - менеджеры с более высоким приоритетом получают больше заказов</li>
                    </ul>
                </small>
            </div>
            
            <div class="form-group mb-3">
                <label for="source">Источник заказов</label>
                <select class="form-control" id="source" name="source" required>
                    <option value="retailcrm" <?= (isset($queue) && $queue['source'] == 'retailcrm') ? 'selected' : '' ?>>RetailCRM</option>
                    <option value="bitrix24" <?= (isset($queue) && $queue['source'] == 'bitrix24') ? 'selected' : '' ?>>Bitrix24</option>
                    <option value="manual" <?= (isset($queue) && $queue['source'] == 'manual') ? 'selected' : '' ?>>Ручное добавление</option>
                </select>
            </div>
            
            <div class="form-group mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= (isset($queue) && $queue['is_active'] == 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">
                        Активна
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Сохранить</button>
            <a href="/queues" class="btn btn-secondary">Отмена</a>
        </form>
    </div>
</div>