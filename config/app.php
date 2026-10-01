<?php

declare(strict_types=1);

/**
 * Конфигурация приложения.
 *
 * Возвращает массив значений, прочитанных из переменных окружения.
 * Значения по умолчанию рассчитаны на локальную разработку.
 *
 * @return array<string, mixed>
 */
return [
    // Название приложения. Используется в <title> и в логах.
    'name' => $_ENV['APP_NAME'] ?? 'Blog',

    // Окружение: local, staging, production.
    'env' => $_ENV['APP_ENV'] ?? 'production',

    // Режим отладки. Включает display_errors и рендер деталей в 500.
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL),

    // Базовый URL. Нужен для абсолютных ссылок, если они понадобятся.
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',

    // Абсолютные пути до ключевых директорий проекта.
    'base_path'      => dirname(__DIR__),
    'views_path'     => dirname(__DIR__) . '/resources/views',
    'smarty_cache'   => dirname(__DIR__) . '/storage/smarty/cache',
    'smarty_compile' => dirname(__DIR__) . '/storage/smarty/compile',
];
