<?php

declare(strict_types=1);

/**
 * Конфигурация подключения к MySQL.
 *
 * Возвращает DSN-параметры и учётные данные. Используется классом
 * App\Database\Connection. В Docker хост - "mysql" (имя сервиса).
 *
 * @return array{
 *     host: string, port: int, database: string,
 *     username: string, password: string, charset: string
 * }
 */
return [
    'host'     => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'port'     => (int) ($_ENV['DB_PORT'] ?? 3306),
    'database' => $_ENV['DB_NAME'] ?? 'blog',
    'username' => $_ENV['DB_USER'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset'  => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
];
