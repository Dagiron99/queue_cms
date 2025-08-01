<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?>CMS распределения заказов</title>
    
    <!-- Bootstrap и основные стили -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/main.css">
    
    <!-- DataTables (для таблиц с пагинацией) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <!-- Дополнительные стили для конкретной страницы -->
    <?php if (isset($pageStyles) && is_array($pageStyles)): ?>
        <?php foreach ($pageStyles as $style): ?>
            <link rel="stylesheet" href="<?php echo $style; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Боковое меню -->
            <div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse" id="sidebarMenu">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h5 class="text-white">CMS распределения заказов</h5>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $_SERVER['REQUEST_URI'] === '/' ? 'active' : ''; ?>" href="/">
                                <i class="bi bi-speedometer2 me-2"></i>
                                Панель управления
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/managers') === 0 ? 'active' : ''; ?>" href="/managers">
                                <i class="bi bi-people me-2"></i>
                                Менеджеры
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/queues') === 0 ? 'active' : ''; ?>" href="/queues">
                                <i class="bi bi-list-ol me-2"></i>
                                Очереди
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/orders_tracking') === 0 ? 'active' : ''; ?>" href="/orders_tracking">
                                <i class="bi bi-cart-check me-2"></i>
                                Отслеживание заказов
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/logs') === 0 ? 'active' : ''; ?>" href="/logs">
                                <i class="bi bi-journal-text me-2"></i>
                                Логи
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/settings/integration') === 0 ? 'active' : ''; ?>" href="/settings/integration">
                                <i class="bi bi-gear me-2"></i>
                                Настройки
                            </a>
                        </li>
                    </ul>
                    
                    <hr class="text-white-50">
                    
                    <div class="user-info text-white-50">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-person-circle me-2 fs-4"></i>
                            <span><?php echo htmlspecialchars(getCurrentUser()['username'] ?? 'Dagiron99'); ?></span>
                        </div>
                        <div class="small">
                            Последний вход: <?php echo date('d.m.Y H:i'); ?>
                        </div>
                        <a href="/logout" class="btn btn-outline-light btn-sm mt-2">
                            <i class="bi bi-box-arrow-right me-1"></i>Выход
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Основной контент -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Кнопка переключения бокового меню на мобильных устройствах -->
                <button class="btn btn-sm btn-outline-secondary d-md-none mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                    <i class="bi bi-list"></i> Меню
                </button>
                
                <!-- Сообщение об ошибке/успехе (флеш-сообщения) -->
                <?php if (function_exists('displayFlashMessage')) displayFlashMessage(); ?>
                
                <!-- Основной контент страницы -->
                <?php echo $content; ?>
                
                <!-- Подвал -->
                <footer class="bg-light py-3 mt-5">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> CMS распределения заказов</p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <p class="text-muted mb-0">Версия 1.0.0</p>
                            </div>
                        </div>
                    </div>
                </footer>
            </main>
        </div>
    </div>
    
    <!-- JavaScript библиотеки -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Общий JavaScript -->
    <script src="/js/app.js"></script>
    
    <!-- Дополнительные скрипты для конкретной страницы -->
    <?php if (isset($pageScripts) && is_array($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>