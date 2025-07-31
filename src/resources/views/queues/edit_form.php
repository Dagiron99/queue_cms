<div class="card mt-4">
    <div class="card-header">
        Менеджеры в очереди "<?= $queue['name'] ?>"
    </div>
    <div class="card-body">
        <?php if (empty($queueManagers)): ?>
            <div class="alert alert-warning">
                Нет менеджеров в очереди. Добавьте менеджеров для распределения заказов.
            </div>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя</th>
                        <th>Email</th>
                        <th>Телефон</th>
                        <th>Приоритет</th>
                        <th>Специализация</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($queueManagers as $qm): ?>
                        <tr>
                            <td><?= $qm['id'] ?></td>
                            <td><?= $qm['name'] ?></td>
                            <td><?= $qm['email'] ?></td>
                            <td><?= $qm['phone'] ?></td>
                            <td>
                                <form class="priority-form d-flex align-items-center" data-manager-id="<?= $qm['id'] ?>">
                                    <input type="number" class="form-control form-control-sm me-2" 
                                           min="1" max="100" value="<?= $qm['priority'] ?? 10 ?>" 
                                           name="priority" style="width: 70px;">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-save"></i>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <?php 
                                $specializations = !empty($qm['specializations']) 
                                    ? json_decode($qm['specializations'], true) 
                                    : [];
                                echo !empty($specializations) 
                                    ? implode(', ', $specializations) 
                                    : '<span class="text-muted">Не указано</span>';
                                ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-2" 
                                        data-bs-toggle="modal" data-bs-target="#specializationModal<?= $qm['id'] ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <!-- Модальное окно для редактирования специализаций -->
                                <div class="modal fade" id="specializationModal<?= $qm['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Специализации менеджера <?= $qm['name'] ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form class="specialization-form" data-manager-id="<?= $qm['id'] ?>">
                                                    <?php foreach ($categories as $category): ?>
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="checkbox" 
                                                                   name="specializations[]" value="<?= $category ?>" 
                                                                   id="spec<?= $qm['id'] ?>_<?= $category ?>"
                                                                   <?= in_array($category, $specializations) ? 'checked' : '' ?>>
                                                            <label class="form-check-label" for="spec<?= $qm['id'] ?>_<?= $category ?>">
                                                                <?= $category ?>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                    <button type="submit" class="btn btn-primary mt-3">Сохранить</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?= $qm['is_active'] ? 'success' : 'danger' ?>">
                                    <?= $qm['is_active'] ? 'Активен' : 'Неактивен' ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-danger remove-manager" data-manager-id="<?= $qm['id'] ?>">
                                    <i class="fas fa-times"></i> Удалить
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <!-- Кнопка добавления менеджера -->
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addManagerModal">
            <i class="fas fa-plus"></i> Добавить менеджера
        </button>
    </div>
</div>

<!-- Модальное окно добавления менеджера в очередь -->
<?php include 'add_manager_modal.php'; ?>

<!-- JavaScript для обработки изменений приоритетов и специализаций -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработка форм изменения приоритета
    document.querySelectorAll('.priority-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const managerId = this.dataset.managerId;
            const priority = this.querySelector('input[name="priority"]').value;
            
            fetch('/queues/update-priority', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    queue_id: <?= $queue['id'] ?>,
                    manager_id: managerId,
                    priority: priority
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Приоритет успешно обновлен', 'success');
                } else {
                    showAlert('Ошибка при обновлении приоритета: ' + data.message, 'danger');
                }
            });
        });
    });
    
    // Обработка форм изменения специализаций
    document.querySelectorAll('.specialization-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const managerId = this.dataset.managerId;
            
            // Собираем выбранные специализации
            const specializations = Array.from(this.querySelectorAll('input[name="specializations[]"]:checked'))
                .map(checkbox => checkbox.value);
            
            fetch('/queues/update-specializations', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    manager_id: managerId,
                    specializations: specializations
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Закрываем модальное окно после успешного сохранения
                    bootstrap.Modal.getInstance(document.getElementById('specializationModal' + managerId)).hide();
                    showAlert('Специализации успешно обновлены', 'success');
                    
                    // Обновляем отображение специализаций в таблице
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showAlert('Ошибка при обновлении специализаций: ' + data.message, 'danger');
                }
            });
        });
    });
    
    // Функция для отображения уведомлений
    function showAlert(message, type) {
        const alertElement = document.createElement('div');
        alertElement.className = `alert alert-${type} alert-dismissible fade show`;
        alertElement.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        document.querySelector('.card-body').prepend(alertElement);
        
        // Автоматически скрываем через 3 секунды
        setTimeout(() => {
            alertElement.classList.remove('show');
            setTimeout(() => alertElement.remove(), 150);
        }, 3000);
    }
});
</script>