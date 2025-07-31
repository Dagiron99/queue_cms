<div class="row mb-4">
    <div class="col-md-6">
        <div class="d-flex align-items-center">
            <i class="bi bi-people fs-1 me-3 text-primary"></i>
            <div>
                <h5 class="mb-0">Управление менеджерами</h5>
                <p class="text-muted mb-0">Добавление, редактирование и удаление менеджеров</p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addManagerModal">
                <i class="bi bi-plus-circle me-2"></i>Добавить менеджера
            </button>
        </div>
    </div>
</div>

<!-- Таблица менеджеров -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center">
        <i class="bi bi-table me-2"></i>
        <span>Список менеджеров</span>
    </div>
    <div class="card-body">
        <div class="table-container">
        <table class="table table-striped table-hover datatable">
            <thead>
                <tr>
                    <th>SYSTEM ID</th>
                    <th>Имя менеджера</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($managers as $manager): ?>
                    <tr>
                    <td><?php echo $manager['retailcrm_id'] ? $manager['retailcrm_id'] : '-'; ?></td>
                        <td><?php echo htmlspecialchars($manager['name']); ?></td>
                        <td>
                            <?php if ($manager['is_active']): ?>
                                <span class="badge bg-success">Активен</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Неактивен</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-primary btn-sm edit-manager" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editManagerModal"
                                    data-id="<?php echo $manager['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($manager['name']); ?>"
                                    data-bitrix24_id="<?php echo $manager['bitrix24_id']; ?>"
                                    data-retailcrm_id="<?php echo $manager['retailcrm_id']; ?>"
                                    data-is_active="<?php echo $manager['is_active']; ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm delete-manager"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#deleteManagerModal"
                                    data-id="<?php echo $manager['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($manager['name']); ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Модальное окно добавления менеджера -->
<div class="modal fade" id="addManagerModal" tabindex="-1" aria-labelledby="addManagerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addManagerModalLabel">Добавление менеджера</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addManagerForm" method="post" action="/managers/store" data-validate="true">
                    <div class="mb-3">
                        <label for="name" class="form-label">Имя менеджера</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback" id="name-feedback" style="display: none;"></div>
                    </div>

                    <div class="mb-3">
                        <label for="bitrix24_id" class="form-label">ID менеджера в Битрикс24</label>
                        <input type="number" class="form-control" id="bitrix24_id" name="bitrix24_id" min="1" pattern="\d*" inputmode="numeric">
                        <div class="invalid-feedback" id="bitrix24_id-feedback" style="display: none;">Укажите числовой ID</div>
                    </div>

                    <div class="mb-3">
                        <label for="retailcrm_id" class="form-label">ID менеджера в RetailCRM</label>
                        <input type="number" class="form-control" id="retailcrm_id" name="retailcrm_id" min="1" pattern="\d*" inputmode="numeric">
                        <div class="invalid-feedback" id="retailcrm_id-feedback" style="display: none;">Укажите числовой ID</div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                        <label class="form-check-label" for="is_active">Активен</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" class="btn btn-primary" form="addManagerForm">Добавить</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования менеджера -->
<div class="modal fade" id="editManagerModal" tabindex="-1" aria-labelledby="editManagerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editManagerModalLabel">Редактирование менеджера</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editManagerForm" method="post" action="/managers/update" data-validate="true">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Имя менеджера</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                        <div class="invalid-feedback" id="edit_name-feedback" style="display: none;"></div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_bitrix24_id" class="form-label">ID менеджера в Битрикс24</label>
                        <input type="number" class="form-control" id="edit_bitrix24_id" name="bitrix24_id" min="1" pattern="\d*" inputmode="numeric">
                        <div class="invalid-feedback" id="edit_bitrix24_id-feedback" style="display: none;">Укажите числовой ID</div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_retailcrm_id" class="form-label">ID менеджера в RetailCRM</label>
                        <input type="number" class="form-control" id="edit_retailcrm_id" name="retailcrm_id" min="1" pattern="\d*" inputmode="numeric">
                        <div class="invalid-feedback" id="edit_retailcrm_id-feedback" style="display: none;">Укажите числовой ID</div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_is_active" name="is_active">
                        <label class="form-check-label" for="edit_is_active">Активен</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" class="btn btn-primary" form="editManagerForm">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно удаления менеджера -->
<div class="modal fade" id="deleteManagerModal" tabindex="-1" aria-labelledby="deleteManagerModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteManagerModalLabel">Удаление менеджера</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите удалить менеджера <strong id="delete_manager_name"></strong>?</p>
                <p class="text-danger">Это действие нельзя отменить.</p>
                <form id="deleteManagerForm" method="post" action="/managers/delete">
                    <input type="hidden" name="id" id="delete_manager_id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" class="btn btn-danger" form="deleteManagerForm">Удалить</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработчики для кнопок редактирования
    document.querySelectorAll('.edit-manager').forEach(function(button) {
        button.addEventListener('click', function() {
            const managerId = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const bitrix24Id = this.getAttribute('data-bitrix24_id');
            const retailcrmId = this.getAttribute('data-retailcrm_id');
            const isActive = this.getAttribute('data-is_active') === '1';
            
            document.getElementById('edit_id').value = managerId;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_bitrix24_id').value = bitrix24Id || '';
            document.getElementById('edit_retailcrm_id').value = retailcrmId || '';
            document.getElementById('edit_is_active').checked = isActive;
            
            // Обновляем action формы, чтобы включить ID менеджера
            const form = document.getElementById('editManagerForm');
            form.action = '/managers/update/' + managerId;
        });
    });
    
    // Обработчики для кнопок удаления
    document.querySelectorAll('.delete-manager').forEach(function(button) {
        button.addEventListener('click', function() {
            const managerId = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            
            document.getElementById('delete_manager_id').value = managerId;
            document.getElementById('delete_manager_name').textContent = name;
            
            // Обновляем action формы, чтобы включить ID менеджера
            const form = document.getElementById('deleteManagerForm');
            form.action = '/managers/delete/' + managerId;
        });
    });
    
    // Валидация форм перед отправкой
    document.querySelectorAll('form[data-validate="true"]').forEach(function(form) {
        form.addEventListener('submit', function(event) {
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
        form.querySelectorAll('[required]').forEach(function(input) {
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