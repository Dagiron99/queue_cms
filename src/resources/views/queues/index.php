<div class="row mb-4">
    <div class="col-md-6">
        <div class="d-flex align-items-center">
            <i class="bi bi-list-ol fs-1 me-3 text-primary"></i>
            <div>
                <h5 class="mb-0">Управление очередями</h5>
                <p class="text-muted mb-0">Настройка очередей для распределения заказов</p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addQueueModal">
                <i class="bi bi-plus-circle me-2"></i>Добавить очередь
            </button>
        </div>
    </div>
</div>

<!-- Таблица очередей -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center">
        <i class="bi bi-table me-2"></i>
        <span>Список очередей</span>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="table table-striped table-hover datatable">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>Тип очереди</th>
                        <th>Макс. нагрузка</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($queues as $queue): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($queue['name']); ?></td>
                            <td><?php echo htmlspecialchars($queue['type']); ?></td>
                            <td><?php echo (int)$queue['max_load']; ?></td>
                            <td>
                                <?php if ($queue['is_active']): ?>
                                    <span class="badge bg-success">Активна</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Неактивна</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-primary btn-sm edit-queue" data-bs-toggle="modal"
                                        data-bs-target="#editQueueModal"
                                        data-id="<?php echo $queue['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($queue['name']); ?>"
                                        data-type="<?php echo htmlspecialchars($queue['type']); ?>"
                                        data-max_load="<?php echo (int)$queue['max_load']; ?>"
                                        data-is_active="<?php echo $queue['is_active']; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm reset-queue" data-bs-toggle="modal"
                                        data-bs-target="#resetQueueModal" data-id="<?php echo $queue['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($queue['name']); ?>">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm delete-queue" data-bs-toggle="modal"
                                        data-bs-target="#deleteQueueModal" data-id="<?php echo $queue['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($queue['name']); ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Карточки менеджеров в очередях -->
<div class="row">
    <?php foreach ($queues as $queue): ?>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-people me-2"></i>
                        <span><?php echo htmlspecialchars($queue['name']); ?></span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary add-manager-to-queue" data-bs-toggle="modal"
                        data-bs-target="#addManagerToQueueModal" data-queue-id="<?php echo $queue['id']; ?>"
                        data-queue-name="<?php echo htmlspecialchars($queue['name']); ?>">
                        <i class="bi bi-plus-circle me-1"></i>Добавить менеджера
                    </button>
                </div>
                <div class="card-body">
                    <?php
                    // Получаем менеджеров для текущей очереди
                    $queueManagers = isset($queueManagersMap[$queue['id']]) ? $queueManagersMap[$queue['id']] : [];

                    if (empty($queueManagers)):
                        ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i> В данной очереди нет менеджеров.
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Менеджер</th>
                                        <th>Макс. нагрузка</th>
                                        <th>Текущая нагрузка</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($queueManagers as $manager): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($manager['name']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo $manager['max_load']; ?></span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <?php
                                                    $loadPercentage = ($manager['current_load'] / $manager['max_load']) * 100;
                                                    $loadClass = 'bg-success';

                                                    if ($loadPercentage > 80) {
                                                        $loadClass = 'bg-danger';
                                                    } elseif ($loadPercentage > 50) {
                                                        $loadClass = 'bg-warning';
                                                    }
                                                    ?>
                                                    <div class="progress-bar <?php echo $loadClass; ?>" role="progressbar"
                                                        style="width: <?php echo $loadPercentage; ?>%"
                                                        aria-valuenow="<?php echo $loadPercentage; ?>" aria-valuemin="0"
                                                        aria-valuemax="100">
                                                        <?php echo $manager['current_load']; ?> /
                                                        <?php echo $manager['max_load']; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-danger remove-manager-from-queue"
                                                    data-bs-toggle="modal" data-bs-target="#removeManagerFromQueueModal"
                                                    data-queue-id="<?php echo $queue['id']; ?>"
                                                    data-manager-id="<?php echo $manager['id']; ?>"
                                                    data-manager-name="<?php echo htmlspecialchars($manager['name']); ?>"
                                                    data-queue-name="<?php echo htmlspecialchars($queue['name']); ?>">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>



<!-- Модальное окно добавления очереди -->
<?php include __DIR__ . '/form.php'; ?>


<!-- Модальное окно редактирования очереди -->
<?php include __DIR__ . '/edit_form.php'; ?>

<!-- Модальное окно сброса очереди -->
<div class="modal fade" id="resetQueueModal" tabindex="-1" aria-labelledby="resetQueueModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetQueueModalLabel">Сброс очереди</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите сбросить очередь <strong id="reset_queue_name"></strong>?</p>
                <p>Будет сброшена текущая позиция в очереди и нагрузка всех менеджеров.</p>
                <p class="text-warning"><i class="bi bi-exclamation-triangle me-2"></i>Это действие может повлиять на
                    распределение заказов.</p>
                <form id="resetQueueForm" method="post" action="/queues/reset">
                    <input type="hidden" name="id" id="reset_queue_id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" class="btn btn-warning" form="resetQueueForm">Сбросить</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно удаления очереди -->
<div class="modal fade" id="deleteQueueModal" tabindex="-1" aria-labelledby="deleteQueueModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteQueueModalLabel">Удаление очереди</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите удалить очередь <strong id="delete_queue_name"></strong>?</p>
                <p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Это действие нельзя отменить.</p>
                <form id="deleteQueueForm" method="post" action="/queues/delete">
                    <input type="hidden" name="id" id="delete_queue_id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" class="btn btn-danger" form="deleteQueueForm">Удалить</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно добавления менеджера в очередь -->
<?php include __DIR__ . '/add_manager_modal.php'; ?>

<!-- Модальное окно удаления менеджера из очереди -->
<div class="modal fade" id="removeManagerFromQueueModal" tabindex="-1"
    aria-labelledby="removeManagerFromQueueModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="removeManagerFromQueueModalLabel">Удаление менеджера из очереди</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите удалить менеджера <strong id="remove_manager_name"></strong> из очереди
                    <strong id="remove_queue_name"></strong>?</p>
                <p class="text-warning"><i class="bi bi-exclamation-triangle me-2"></i>Если у менеджера есть активные
                    заказы в этой очереди, их обработка может быть нарушена.</p>
                <form id="removeManagerFromQueueForm" method="post" action="/queues/remove-manager">
                    <input type="hidden" name="queue_id" id="remove_queue_id">
                    <input type="hidden" name="manager_id" id="remove_manager_id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" class="btn btn-danger" form="removeManagerFromQueueForm">Удалить</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
// Обработчики для кнопок редактирования очереди
document.querySelectorAll('.edit-queue').forEach(function (button) {
    button.addEventListener('click', function () {
        const queueId = this.getAttribute('data-id');
        const name = this.getAttribute('data-name');
        const type = this.getAttribute('data-type');
        const maxLoad = this.getAttribute('data-max_load');
        const isActive = this.getAttribute('data-is_active') === '1';

        document.getElementById('edit_id').value = queueId;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_type').value = type;
        document.getElementById('edit_max_load').value = maxLoad;
        document.getElementById('edit_is_active').checked = isActive;

        // Обновляем action формы, чтобы включить ID очереди
        const form = document.getElementById('editQueueForm');
        form.action = '/queues/update/' + queueId;
    });
});

        // Обработчики для кнопок сброса очереди
        document.querySelectorAll('.reset-queue').forEach(function (button) {
            button.addEventListener('click', function () {
                const queueId = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');

                document.getElementById('reset_queue_id').value = queueId;
                document.getElementById('reset_queue_name').textContent = name;

                // Обновляем action формы, чтобы включить ID очереди
                const form = document.getElementById('resetQueueForm');
                form.action = '/queues/reset/' + queueId;
            });
        });

        // Обработчики для кнопок удаления очереди
        document.querySelectorAll('.delete-queue').forEach(function (button) {
            button.addEventListener('click', function () {
                const queueId = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');

                document.getElementById('delete_queue_id').value = queueId;
                document.getElementById('delete_queue_name').textContent = name;

                // Обновляем action формы, чтобы включить ID очереди
                const form = document.getElementById('deleteQueueForm');
                form.action = '/queues/delete/' + queueId;
            });
        });

        // Обработчики для кнопок добавления менеджера в очередь
        document.querySelectorAll('.add-manager-to-queue').forEach(function (button) {
            button.addEventListener('click', function () {
                const queueId = this.getAttribute('data-queue-id');
                const queueName = this.getAttribute('data-queue-name');
                const queueType = this.getAttribute('data-queue-type');

                // Подгрузка доступных менеджеров для выбранной очереди через аякс или подменой переменных через сервер
                // Здесь можно сделать аякс-запрос на сервер для получения $availableManagers и $currentQueue по queueId
                // После получения — обновить select#manager_id и скрывать/показывать чекбокс fallback

                document.getElementById('add_manager_queue_id').value = queueId;
                document.getElementById('queue_name_display').value = queueName;

                // Показывать/скрывать чекбокс fallback
                var fallbackCheck = document.getElementById('is_fallback_group');
                if (fallbackCheck) {
                    if (queueType === 'online-fallback') {
                        fallbackCheck.style.display = 'block';
                    } else {
                        fallbackCheck.style.display = 'none';
                    }
                }
            });
        });

        // Обработчики для кнопок удаления менеджера из очереди
        document.querySelectorAll('.remove-manager-from-queue').forEach(function (button) {
            button.addEventListener('click', function () {
                const queueId = this.getAttribute('data-queue-id');
                const managerId = this.getAttribute('data-manager-id');
                const managerName = this.getAttribute('data-manager-name');
                const queueName = this.getAttribute('data-queue-name');

                document.getElementById('remove_queue_id').value = queueId;
                document.getElementById('remove_manager_id').value = managerId;
                document.getElementById('remove_manager_name').textContent = managerName;
                document.getElementById('remove_queue_name').textContent = queueName;
            });
        });

        // Валидация форм перед отправкой
        document.querySelectorAll('form[data-validate="true"]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!validateForm(form.id)) {
                    event.preventDefault();
                }
            });
        });

        // Функция валидации формы
        function validateForm(formId) {
            const form = document.getElementById(formId);
            let isValid = true;

            // Проверка обязательных полей
            form.querySelectorAll('[required]').forEach(function (input) {
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    const feedbackId = input.id + '-feedback';
                    const feedbackElement = document.getElementById(feedbackId);
                    if (feedbackElement) {
                        feedbackElement.textContent = 'Это поле обязательно для заполнения';
                        feedbackElement.style.display = 'block';
                    }
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                    const feedbackId = input.id + '-feedback';
                    const feedbackElement = document.getElementById(feedbackId);
                    if (feedbackElement) {
                        feedbackElement.style.display = 'none';
                    }
                }
            });

            return isValid;
        }
    });
</script>