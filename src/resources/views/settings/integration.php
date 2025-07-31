<?php
/**
 * Страница настроек интеграций
 * 
 * Позволяет настроить подключение к Битрикс24 и RetailCRM
 */

// Получаем текущие настройки из БД
$bitrixSettings = $Settings->getSettings('bitrix24');
$retailCrmSettings = $Settings->getSettings('retailcrm');

// Определяем активную вкладку
$activeTab = $_GET['tab'] ?? 'bitrix24';
?>

<div class="container-fluid py-4">
    <!-- Заголовок и информация -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Настройки интеграций</h1>
            <p class="mb-0 text-muted">Настройте параметры подключения к внешним системам</p>
        </div>
        <div class="text-end">
            <div class="small text-muted">Текущая дата: <?php echo date('Y-m-d H:i:s'); ?> (UTC)</div>
            <div class="small text-muted">Пользователь: <?php echo htmlspecialchars($_SESSION['user_login'] ?? 'Dagiron99'); ?></div>
        </div>
    </div>

    <!-- Вкладки -->
    <ul class="nav nav-tabs" id="integrationTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $activeTab == 'bitrix24' ? 'active' : ''; ?>" 
                    id="bitrix24-tab" 
                    data-bs-toggle="tab" 
                    data-bs-target="#bitrix24-content" 
                    type="button" 
                    role="tab" 
                    aria-controls="bitrix24-content" 
                    aria-selected="<?php echo $activeTab == 'bitrix24' ? 'true' : 'false'; ?>">
                <i class="bi bi-building me-2"></i>Битрикс24
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $activeTab == 'retailcrm' ? 'active' : ''; ?>" 
                    id="retailcrm-tab" 
                    data-bs-toggle="tab" 
                    data-bs-target="#retailcrm-content" 
                    type="button" 
                    role="tab" 
                    aria-controls="retailcrm-content" 
                    aria-selected="<?php echo $activeTab == 'retailcrm' ? 'true' : 'false'; ?>">
                <i class="bi bi-shop me-2"></i>RetailCRM
            </button>
        </li>
    </ul>

    <!-- Содержимое вкладок -->
    <div class="tab-content p-4 border border-top-0 rounded-bottom" id="integrationTabsContent">
        <!-- Вкладка Битрикс24 -->
        <div class="tab-pane fade <?php echo $activeTab == 'bitrix24' ? 'show active' : ''; ?>" 
             id="bitrix24-content" 
             role="tabpanel" 
             aria-labelledby="bitrix24-tab">
            
            <form id="bitrix24-form" method="post" action="/settings/save">
                <input type="hidden" name="integration_type" value="bitrix24">
                
                <div class="alert alert-info mb-4">
                    <div class="d-flex">
                        <div class="me-3">
                            <i class="bi bi-info-circle fs-4"></i>
                        </div>
                        <div>
                            <h5 class="alert-heading">Информация о Битрикс24</h5>
                            <p class="mb-0">Для интеграции с Битрикс24 необходимо создать входящий вебхук в настройках вашего портала.</p>
                            <a href="https://helpdesk.bitrix24.ru/open/17865378/" target="_blank" class="alert-link">Инструкция по созданию вебхука</a>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="m-0">Основные настройки</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="bitrix_portal_url" class="form-label">URL портала Битрикс24</label>
                                    <input type="url" class="form-control" id="bitrix_portal_url" name="settings[portal_url]" 
                                           value="<?php echo htmlspecialchars($bitrixSettings['portal_url'] ?? ''); ?>" 
                                           placeholder="https://example.bitrix24.ru">
                                    <div class="form-text">Укажите адрес вашего портала Битрикс24 без слэша в конце</div>
                                </div>

                                <div class="mb-3">
                                    <label for="bitrix_webhook" class="form-label">Ключ вебхука</label>
                                    <input type="text" class="form-control" id="bitrix_webhook" name="settings[webhook]" 
                                           value="<?php echo htmlspecialchars($bitrixSettings['webhook'] ?? ''); ?>" 
                                           placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                                    <div class="form-text">Введите ключ входящего вебхука из настроек Битрикс24</div>
                                </div>

                                <div class="mb-3">
                                    <label for="bitrix_responsible_id" class="form-label">ID ответственного по умолчанию</label>
                                    <input type="text" class="form-control" id="bitrix_responsible_id" name="settings[responsible_id]" 
                                           value="<?php echo htmlspecialchars($bitrixSettings['responsible_id'] ?? ''); ?>" 
                                           placeholder="1">
                                    <div class="form-text">ID пользователя Битрикс24, который будет назначен ответственным</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="m-0">Параметры синхронизации</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Направление синхронизации</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="bitrix_sync_to" name="settings[sync_to]" value="1" 
                                               <?php echo (($bitrixSettings['sync_to'] ?? 0) == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="bitrix_sync_to">Отправлять заказы в Битрикс24</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="bitrix_sync_from" name="settings[sync_from]" value="1" 
                                               <?php echo (($bitrixSettings['sync_from'] ?? 0) == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="bitrix_sync_from">Получать заказы из Битрикс24</label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="bitrix_order_source" class="form-label">Источник заказа</label>
                                    <input type="text" class="form-control" id="bitrix_order_source" name="settings[order_source]" 
                                           value="<?php echo htmlspecialchars($bitrixSettings['order_source'] ?? 'CRM'); ?>" 
                                           placeholder="CRM">
                                    <div class="form-text">Источник, который будет указан при создании заказа в Битрикс24</div>
                                </div>

                                <div class="mb-3">
                                    <label for="bitrix_update_interval" class="form-label">Интервал обновления (мин)</label>
                                    <input type="number" class="form-control" id="bitrix_update_interval" name="settings[update_interval]" 
                                           value="<?php echo intval($bitrixSettings['update_interval'] ?? 30); ?>" 
                                           min="5" max="1440">
                                    <div class="form-text">Как часто проверять новые данные (минимум 5 минут)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="m-0">Соответствие статусов</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="bitrix_status_new" class="form-label">Новый заказ</label>
                                <input type="text" class="form-control" id="bitrix_status_new" name="settings[statuses][new]" 
                                       value="<?php echo htmlspecialchars($bitrixSettings['statuses']['new'] ?? 'NEW'); ?>" 
                                       placeholder="NEW">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="bitrix_status_processing" class="form-label">В обработке</label>
                                <input type="text" class="form-control" id="bitrix_status_processing" name="settings[statuses][processing]" 
                                       value="<?php echo htmlspecialchars($bitrixSettings['statuses']['processing'] ?? 'IN_PROCESS'); ?>" 
                                       placeholder="IN_PROCESS">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="bitrix_status_completed" class="form-label">Выполнен</label>
                                <input type="text" class="form-control" id="bitrix_status_completed" name="settings[statuses][completed]" 
                                       value="<?php echo htmlspecialchars($bitrixSettings['statuses']['completed'] ?? 'COMPLETED'); ?>" 
                                       placeholder="COMPLETED">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="bitrix_status_canceled" class="form-label">Отменен</label>
                                <input type="text" class="form-control" id="bitrix_status_canceled" name="settings[statuses][canceled]" 
                                       value="<?php echo htmlspecialchars($bitrixSettings['statuses']['canceled'] ?? 'CANCELED'); ?>" 
                                       placeholder="CANCELED">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" id="test-bitrix-connection">
                        <i class="bi bi-check-circle me-2"></i>Проверить соединение
                    </button>
                    <div>
                        <button type="reset" class="btn btn-light me-2">Отмена</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Сохранить настройки
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Вкладка RetailCRM -->
        <div class="tab-pane fade <?php echo $activeTab == 'retailcrm' ? 'show active' : ''; ?>" 
             id="retailcrm-content" 
             role="tabpanel" 
             aria-labelledby="retailcrm-tab">
            
            <form id="retailcrm-form" method="post" action="/settings/save">
                <input type="hidden" name="integration_type" value="retailcrm">
                
                <div class="alert alert-info mb-4">
                    <div class="d-flex">
                        <div class="me-3">
                            <i class="bi bi-info-circle fs-4"></i>
                        </div>
                        <div>
                            <h5 class="alert-heading">Информация о RetailCRM</h5>
                            <p class="mb-0">Для интеграции с RetailCRM необходимо создать API-ключ в настройках вашего аккаунта.</p>
                            <a href="https://docs.retailcrm.ru/Developers/API/AccessKeys" target="_blank" class="alert-link">Инструкция по созданию API-ключа</a>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="m-0">Основные настройки</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="retailcrm_url" class="form-label">URL RetailCRM</label>
                                    <input type="url" class="form-control" id="retailcrm_url" name="settings[url]" 
                                           value="<?php echo htmlspecialchars($retailCrmSettings['url'] ?? ''); ?>" 
                                           placeholder="https://your-account.retailcrm.ru">
                                    <div class="form-text">Укажите адрес вашего аккаунта RetailCRM без слэша в конце</div>
                                </div>

                                <div class="mb-3">
                                    <label for="retailcrm_api_key" class="form-label">API ключ</label>
                                    <input type="text" class="form-control" id="retailcrm_api_key" name="settings[api_key]" 
                                           value="<?php echo htmlspecialchars($retailCrmSettings['api_key'] ?? ''); ?>" 
                                           placeholder="API ключ RetailCRM">
                                    <div class="form-text">Введите API ключ, созданный в личном кабинете RetailCRM</div>
                                </div>

                                <div class="mb-3">
                                    <label for="retailcrm_site" class="form-label">Код сайта</label>
                                    <input type="text" class="form-control" id="retailcrm_site" name="settings[site]" 
                                           value="<?php echo htmlspecialchars($retailCrmSettings['site'] ?? ''); ?>" 
                                           placeholder="shop">
                                    <div class="form-text">Код сайта в RetailCRM (обычно "shop" или "main")</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="m-0">Параметры синхронизации</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Направление синхронизации</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="retailcrm_sync_to" name="settings[sync_to]" value="1" 
                                               <?php echo (($retailCrmSettings['sync_to'] ?? 0) == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="retailcrm_sync_to">Отправлять заказы в RetailCRM</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="retailcrm_sync_from" name="settings[sync_from]" value="1" 
                                               <?php echo (($retailCrmSettings['sync_from'] ?? 0) == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="retailcrm_sync_from">Получать заказы из RetailCRM</label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="retailcrm_order_type" class="form-label">Тип заказа</label>
                                    <select class="form-select" id="retailcrm_order_type" name="settings[order_type]">
                                        <option value="eshop-individual" <?php echo ($retailCrmSettings['order_type'] ?? '') == 'eshop-individual' ? 'selected' : ''; ?>>
                                            Физическое лицо
                                        </option>
                                        <option value="eshop-legal" <?php echo ($retailCrmSettings['order_type'] ?? '') == 'eshop-legal' ? 'selected' : ''; ?>>
                                            Юридическое лицо
                                        </option>
                                    </select>
                                    <div class="form-text">Тип создаваемых заказов в RetailCRM</div>
                                </div>

                                <div class="mb-3">
                                    <label for="retailcrm_update_interval" class="form-label">Интервал обновления (мин)</label>
                                    <input type="number" class="form-control" id="retailcrm_update_interval" name="settings[update_interval]" 
                                           value="<?php echo intval($retailCrmSettings['update_interval'] ?? 15); ?>" 
                                           min="5" max="1440">
                                    <div class="form-text">Как часто проверять новые данные (минимум 5 минут)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="m-0">Соответствие статусов</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="retailcrm_status_new" class="form-label">Новый заказ</label>
                                <input type="text" class="form-control" id="retailcrm_status_new" name="settings[statuses][new]" 
                                       value="<?php echo htmlspecialchars($retailCrmSettings['statuses']['new'] ?? 'new'); ?>" 
                                       placeholder="new">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="retailcrm_status_processing" class="form-label">В обработке</label>
                                <input type="text" class="form-control" id="retailcrm_status_processing" name="settings[statuses][processing]" 
                                       value="<?php echo htmlspecialchars($retailCrmSettings['statuses']['processing'] ?? 'processing'); ?>" 
                                       placeholder="processing">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="retailcrm_status_completed" class="form-label">Выполнен</label>
                                <input type="text" class="form-control" id="retailcrm_status_completed" name="settings[statuses][completed]" 
                                       value="<?php echo htmlspecialchars($retailCrmSettings['statuses']['completed'] ?? 'complete'); ?>" 
                                       placeholder="complete">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="retailcrm_status_canceled" class="form-label">Отменен</label>
                                <input type="text" class="form-control" id="retailcrm_status_canceled" name="settings[statuses][canceled]" 
                                       value="<?php echo htmlspecialchars($retailCrmSettings['statuses']['canceled'] ?? 'cancel'); ?>" 
                                       placeholder="cancel">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="m-0">Дополнительные настройки</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="retailcrm_delivery_type" class="form-label">Тип доставки по умолчанию</label>
                                <input type="text" class="form-control" id="retailcrm_delivery_type" name="settings[delivery_type]" 
                                       value="<?php echo htmlspecialchars($retailCrmSettings['delivery_type'] ?? 'self-delivery'); ?>" 
                                       placeholder="self-delivery">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="retailcrm_payment_type" class="form-label">Тип оплаты по умолчанию</label>
                                <input type="text" class="form-control" id="retailcrm_payment_type" name="settings[payment_type]" 
                                       value="<?php echo htmlspecialchars($retailCrmSettings['payment_type'] ?? 'cash'); ?>" 
                                       placeholder="cash">
                            </div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="retailcrm_sync_payments" name="settings[sync_payments]" value="1" 
                                   <?php echo (($retailCrmSettings['sync_payments'] ?? 0) == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="retailcrm_sync_payments">
                                Синхронизировать платежи
                            </label>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="retailcrm_sync_delivery" name="settings[sync_delivery]" value="1" 
                                   <?php echo (($retailCrmSettings['sync_delivery'] ?? 0) == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="retailcrm_sync_delivery">
                                Синхронизировать данные о доставке
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" id="test-retailcrm-connection">
                        <i class="bi bi-check-circle me-2"></i>Проверить соединение
                    </button>
                    <div>
                        <button type="reset" class="btn btn-light me-2">Отмена</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Сохранить настройки
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Индикатор тестирования соединения -->
    <div class="modal fade" id="testConnectionModal" tabindex="-1" aria-labelledby="testConnectionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="testConnectionModalLabel">Проверка соединения</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Проверка соединения...</span>
                        </div>
                    </div>
                    <div id="testConnectionResult"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработчик для кнопки проверки соединения Битрикс24
    document.getElementById('test-bitrix-connection').addEventListener('click', function() {
        const portalUrl = document.getElementById('bitrix_portal_url').value;
        const webhook = document.getElementById('bitrix_webhook').value;
        
        if (!portalUrl || !webhook) {
            alert('Пожалуйста, заполните URL портала и ключ вебхука');
            return;
        }
        
        // Показываем модальное окно
        const testModal = new bootstrap.Modal(document.getElementById('testConnectionModal'));
        testModal.show();
        
        // Отправляем запрос на проверку соединения
        fetch('/api/test-connection', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'bitrix24',
                portal_url: portalUrl,
                webhook: webhook
            })
        })
        .then(response => response.json())
        .then(data => {
            const resultEl = document.getElementById('testConnectionResult');
            
            if (data.success) {
                resultEl.innerHTML = `
                    <div class="alert alert-success">
                        <h5 class="alert-heading">Соединение установлено!</h5>
                        <p>Успешно подключено к Битрикс24.</p>
                        <hr>
                        <p class="mb-0">Информация о портале: ${data.portal_name || 'Не указано'}</p>
                    </div>`;
            } else {
                resultEl.innerHTML = `
                    <div class="alert alert-danger">
                        <h5 class="alert-heading">Ошибка соединения</h5>
                        <p>${data.message || 'Не удалось подключиться к Битрикс24'}</p>
                        <hr>
                        <p class="mb-0">Проверьте правильность введенных данных и доступность портала.</p>
                    </div>`;
            }
        })
        .catch(error => {
            document.getElementById('testConnectionResult').innerHTML = `
                <div class="alert alert-danger">
                    <h5 class="alert-heading">Ошибка соединения</h5>
                    <p>Произошла ошибка при проверке соединения: ${error.message}</p>
                    <hr>
                    <p class="mb-0">Проверьте подключение к интернету и правильность введенных данных.</p>
                </div>`;
        });
    });
    
    // Обработчик для кнопки проверки соединения RetailCRM
    document.getElementById('test-retailcrm-connection').addEventListener('click', function() {
        const crmUrl = document.getElementById('retailcrm_url').value;
        const apiKey = document.getElementById('retailcrm_api_key').value;
        
        if (!crmUrl || !apiKey) {
            alert('Пожалуйста, заполните URL RetailCRM и API ключ');
            return;
        }
        
        // Показываем модальное окно
        const testModal = new bootstrap.Modal(document.getElementById('testConnectionModal'));
        testModal.show();
        
        // Отправляем запрос на проверку соединения
        fetch('/api/test-connection', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'retailcrm',
                url: crmUrl,
                api_key: apiKey
            })
        })
        .then(response => response.json())
        .then(data => {
            const resultEl = document.getElementById('testConnectionResult');
            
            if (data.success) {
                resultEl.innerHTML = `
                    <div class="alert alert-success">
                        <h5 class="alert-heading">Соединение установлено!</h5>
                        <p>Успешно подключено к RetailCRM.</p>
                        <hr>
                        <p class="mb-0">Информация: ${data.crm_name || 'Не указано'}</p>
                    </div>`;
            } else {
                resultEl.innerHTML = `
                    <div class="alert alert-danger">
                        <h5 class="alert-heading">Ошибка соединения</h5>
                        <p>${data.message || 'Не удалось подключиться к RetailCRM'}</p>
                        <hr>
                        <p class="mb-0">Проверьте правильность введенных данных и доступность сервиса.</p>
                    </div>`;
            }
        })
        .catch(error => {
            document.getElementById('testConnectionResult').innerHTML = `
                <div class="alert alert-danger">
                    <h5 class="alert-heading">Ошибка соединения</h5>
                    <p>Произошла ошибка при проверке соединения: ${error.message}</p>
                    <hr>
                    <p class="mb-0">Проверьте подключение к интернету и правильность введенных данных.</p>
                </div>`;
        });
    });
    
    // Предотвращаем отправку формы по нажатию Enter
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && e.target.nodeName !== 'TEXTAREA') {
                e.preventDefault();
            }
        });
    });
    
    // Переключение вкладок с сохранением в URL
    const tabLinks = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabLinks.forEach(tabLink => {
        tabLink.addEventListener('shown.bs.tab', function (e) {
            const tab = e.target.id.replace('-tab', '');
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url);
        });
    });
});
</script>