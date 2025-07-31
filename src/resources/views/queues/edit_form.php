<div class="modal fade" id="editQueueModal" tabindex="-1" aria-labelledby="editQueueModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editQueueModalLabel">Редактирование очереди</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <form id="editQueueForm" method="post" action="/queues/update" data-validate="true">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Название очереди</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_type" class="form-label">Тип очереди</label>
                        <select class="form-select" id="edit_type" name="type" required>
                            <option value="force">Force</option>
                            <option value="online">Online</option>
                            <option value="online-fallback">Online-Fallback</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_max_load" class="form-label">Максимальная нагрузка на менеджера</label>
                        <input type="number" class="form-control" id="edit_max_load" name="max_load" min="1" value="2"
                            required>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_is_active" name="is_active">
                        <label class="form-check-label" for="edit_is_active">Активна</label>
                    </div>

                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </form>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" class="btn btn-primary" form="editQueueForm">Сохранить</button>
            </div>
        </div>
    </div>
</div>