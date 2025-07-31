CREATE DATABASE IF NOT EXISTS queue_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE queue_cms;

-- Таблица менеджеров
CREATE TABLE IF NOT EXISTS managers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    email VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    status ENUM('online', 'offline', 'busy') DEFAULT 'offline',
    is_fallback TINYINT(1) DEFAULT 0,
    external_id VARCHAR(50) NULL,
    external_system ENUM('bitrix24', 'retailcrm', 'none') DEFAULT 'none',
    current_load INT DEFAULT 0,
    max_load INT DEFAULT 5,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица очередей
CREATE TABLE IF NOT EXISTS queues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    algorithm ENUM('round_robin', 'least_busy', 'random') DEFAULT 'round_robin',
    type ENUM('force', 'online', 'online-fallback') DEFAULT 'force',
    current_position INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    max_load INT NULL DEFAULT 2,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица связи менеджеров и очередей
CREATE TABLE IF NOT EXISTS queue_manager (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_id INT NOT NULL,
    manager_id INT NOT NULL,
    position INT NOT NULL,
    FOREIGN KEY (queue_id) REFERENCES queues(id) ON DELETE CASCADE,
    FOREIGN KEY (manager_id) REFERENCES managers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица отношений менеджеров к очередям с лимитами
CREATE TABLE IF NOT EXISTS queue_manager_relations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_id INT NOT NULL,
    manager_id INT NOT NULL,
    max_load INT DEFAULT 2,
    current_load INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    is_fallback TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY queue_manager_unique (queue_id, manager_id),
    FOREIGN KEY (queue_id) REFERENCES queues(id) ON DELETE CASCADE,
    FOREIGN KEY (manager_id) REFERENCES managers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица логов распределения
CREATE TABLE IF NOT EXISTS distribution_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_id INT,
    manager_id INT,
    order_id VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    status VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    description TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (queue_id) REFERENCES queues(id),
    FOREIGN KEY (manager_id) REFERENCES managers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица для отслеживания заказов
CREATE TABLE IF NOT EXISTS orders_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(255) NOT NULL,
    manager_id INT NOT NULL,
    current_status VARCHAR(50) NOT NULL,
    initial_status VARCHAR(50) NOT NULL,
    assigned_at DATETIME NOT NULL,
    last_checked_at DATETIME NOT NULL,
    processed TINYINT(1) DEFAULT 0,
    processed_at DATETIME NULL,
    external_id VARCHAR(100) NULL,
    external_system ENUM('bitrix24', 'retailcrm', 'none') DEFAULT 'none',
    UNIQUE KEY (order_id),
    INDEX (manager_id),
    INDEX (processed),
    INDEX (last_checked_at),
    FOREIGN KEY (manager_id) REFERENCES managers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица настроек системы
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    value TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица настроек интеграций
CREATE TABLE IF NOT EXISTS integration_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    settings TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Системные логи
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
    message TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    context TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT NULL,
    ip_address VARCHAR(45) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Добавление тестовых данных
INSERT INTO managers (name, email, status, is_fallback, external_id, external_system, is_active, max_load) VALUES 
('Менеджер 1', 'manager1@example.com', 'online', 0, '101', 'bitrix24', 1, 5),
('Менеджер 2', 'manager2@example.com', 'online', 0, '102', 'bitrix24', 1, 4),
('Менеджер 3', 'manager3@example.com', 'offline', 1, '103', 'bitrix24', 1, 3),
('Менеджер 4', 'manager4@example.com', 'online', 0, '201', 'retailcrm', 1, 5);

INSERT INTO queues (name, type, algorithm, is_active) VALUES 
('Основная очередь', 'force', 'round_robin', 1),
('Очередь онлайн', 'online', 'least_busy', 1),
('Очередь с резервом', 'online-fallback', 'random', 1);

INSERT INTO queue_manager (queue_id, manager_id, position) VALUES 
(1, 1, 1),
(1, 2, 2),
(1, 3, 3),
(1, 4, 4),
(2, 1, 1),
(2, 2, 2),
(2, 4, 3),
(3, 1, 1),
(3, 2, 2),
(3, 3, 3),
(3, 4, 4);

INSERT INTO queue_manager_relations (queue_id, manager_id, max_load, current_load, is_active) VALUES 
(1, 1, 5, 0, 1),
(1, 2, 4, 0, 1),
(1, 3, 3, 0, 1),
(1, 4, 5, 0, 1),
(2, 1, 4, 0, 1),
(2, 2, 3, 0, 1),
(2, 4, 4, 0, 1),
(3, 1, 5, 0, 1),
(3, 2, 4, 0, 1),
(3, 3, 3, 0, 1),
(3, 4, 5, 0, 1);

-- Начальные настройки
INSERT INTO settings (name, value) VALUES
('system_name', 'Система распределения заказов'),
('system_version', '1.0.0'),
('check_interval', '15'),
('log_retention_days', '30'),
('default_queue_id', '1');

-- Тестовые настройки интеграций
INSERT INTO integration_settings (type, settings, created_at, updated_at) VALUES
('bitrix24', '{"portal_url":"https://your-domain.bitrix24.ru","webhook":"your_webhook_token_here","responsible_id":"1","sync_to":1,"sync_from":1,"order_source":"CRM","update_interval":30,"statuses":{"new":"NEW","processing":"IN_PROCESS","completed":"COMPLETED","canceled":"CANCELED"}}', NOW(), NOW()),
('retailcrm', '{"url":"https://example.retailcrm.ru","api_key":"your_api_key_here","site":"shop","sync_to":1,"sync_from":1,"order_type":"eshop-individual","update_interval":15,"statuses":{"new":"new","processing":"processing","completed":"complete","canceled":"cancel"},"delivery_type":"self-delivery","payment_type":"cash","sync_payments":1,"sync_delivery":1}', NOW(), NOW());

-- Добавление системных логов
INSERT INTO system_logs (level, message, created_at) VALUES
('info', 'Система успешно инициализирована', NOW()),
('success', 'Создана база данных и начальные таблицы', NOW());