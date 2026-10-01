<?php

declare(strict_types=1);

/**
 * CLI-скрипт запуска миграций.
 *
 * Использование:
 *   php bin/migrate.php              - применить все неприменённые
 *   php bin/migrate.php rollback     - откатить одну последнюю
 *   php bin/migrate.php rollback 3   - откатить три последние
 */

use App\Database\Connection;
use App\Database\Migrator;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

// Загружаем .env: safeLoad() не падает, если файла нет.
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$config = require dirname(__DIR__) . '/config/database.php';
$pdo = (new Connection($config))->pdo();

$migrator = new Migrator($pdo, dirname(__DIR__) . '/database/migrations');

$command = $argv[1] ?? 'migrate';

if ($command === 'rollback') {
    $steps = (int) ($argv[2] ?? 1);
    $rolled = $migrator->rollback($steps);

    if ($rolled === []) {
        echo "Нечего откатывать.\n";
        exit(0);
    }

    foreach ($rolled as $name) {
        echo "Откачена: {$name}\n";
    }
    exit(0);
}

// По умолчанию - накат всех неприменённых.
$executed = $migrator->migrate();

if ($executed === []) {
    echo "Все миграции уже применены.\n";
    exit(0);
}

foreach ($executed as $name) {
    echo "Применена: {$name}\n";
}
echo "Готово. Применено миграций: " . count($executed) . "\n";
