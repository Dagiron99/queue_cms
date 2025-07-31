<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Параметры подключения к базе данных
    |--------------------------------------------------------------------------
    */
    'driver' => 'mysql',
    'host' => getenv('DB_HOST') ?: 'mysql',
    'port' => 3306,
    'database' => getenv('DB_DATABASE') ?: 'queue_cms',
    'username' => getenv('DB_USERNAME') ?: 'queue_user',
    'password' => getenv('DB_PASSWORD') ?: 'root',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    
    /*
    |--------------------------------------------------------------------------
    | Настройки PDO
    |--------------------------------------------------------------------------
    */
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];