<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Параметры подключения к базе данных
    |--------------------------------------------------------------------------
    */
    'driver' => 'mysql',
    'host' => 'queue_cms_db', // В Docker-окружении это имя сервиса
    'port' => 3306,
    'database' => 'queue_cms',
    'username' => 'queue_user',
    'password' => 'root',
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