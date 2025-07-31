<div class="modal fade" id="addManagerToQueueModal" tabindex="-1" aria-labelledby="addManagerToQueueModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addManagerToQueueModalLabel">Добавление менеджера в очередь</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <form id="addManagerToQueueForm" method="post" action="/queues/add-manager" data-validate="true">
                    <input type="hidden" name="queue_id" id="add_manager_queue_id">

                    <div class="mb-3">
                        <label for="manager_id" class="form-label">Менеджер</label>
                        <select class="form-select" id="manager_id" name="manager_id" required>
                            <option value="">Выберите менеджера</option>
                            <?php foreach ($availableManagers as $manager): ?>
                                <option value="<?= $manager['id'] ?>"><?= htmlspecialchars($manager['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if (isset($currentQueue) && $currentQueue['type'] === 'online-fallback'): ?>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_fallback" name="is_fallback" value="1">
                            <label class="form-check-label" for="is_fallback">Fallback</label>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary">Добавить</button>
                </form>
            </div>
        </div>
    </div>
</div>