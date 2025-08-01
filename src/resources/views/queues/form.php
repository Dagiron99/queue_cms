<div class="modal fade" id="addQueueModal" tabindex="-1" aria-labelledby="addQueueModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addQueueModalLabel">Добавление очереди</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addQueueForm" method="post" action="/queues/store" data-validate="true">
                    <div class="mb-3">
                        <label for="name" class="form-label">Название очереди</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="type" class="form-label">Тип очереди</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="force">Force</option>
                            <option value="online">Online</option>
                            <option value="online-fallback">Online-Fallback</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="max_load" class="form-label">Максимальная нагрузка на менеджера</label>
                        <input type="number" class="form-control" id="max_load" name="max_load" min="1" value="2"
                            required>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                        <label class="form-check-label" for="is_active">Активна</label>
                    </div>

                    <button type="submit" class="btn btn-primary">Добавить очередь</button>
                </form>
            </div>
        </div>
    </div>
</div>