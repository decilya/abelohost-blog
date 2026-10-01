<?php

declare(strict_types=1);

/**
 * Временная точка входа для проверки окружения.
 *
 * Заменяется настоящим front controller на следующем шаге.
 * Здесь только диагностика: версия PHP, расширения, подключение к MySQL.
 */

// Проверяем наличие расширений, которые нужны приложению.

$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'gd', 'fileinfo', 'json', 'zip', 'Zend OPcache'];

$extensionStatus = [];
foreach ($requiredExtensions as $ext) {
    $extensionStatus[$ext] = extension_loaded($ext);
}

// Пробуем подключиться к MySQL. Если что-то не так - ловим и показываем.
$dbStatus = 'unknown';
$dbError = null;
$dbVersion = null;
try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $_ENV['DB_HOST'] ?? 'mysql',
        $_ENV['DB_PORT'] ?? '3306',
        $_ENV['DB_NAME'] ?? 'blog',
    );
    $pdo = new PDO(
        $dsn,
        $_ENV['DB_USER'] ?? 'blog',
        $_ENV['DB_PASSWORD'] ?? 'secret',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3,
        ],
    );
    $dbStatus = 'ok';
    $dbVersion = $pdo->query('SELECT VERSION()')->fetchColumn();
} catch (Throwable $e) {
    $dbStatus = 'fail';
    $dbError = $e->getMessage();
}

$allExtensionsOk = !in_array(false, $extensionStatus, true);
$overallOk = $allExtensionsOk && $dbStatus === 'ok';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Окружение проекта</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 40px 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f7f7f8; color: #1f2328; line-height: 1.5;
        }
        .wrap { max-width: 720px; margin: 0 auto; }
        h1 { margin-top: 0; }
        .card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 8px;
            padding: 20px; margin-bottom: 20px;
        }
        .row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f2f4; }
        .row:last-child { border-bottom: none; }
        .key { color: #6b7280; }
        .val { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .ok { color: #16a34a; font-weight: 600; }
        .fail { color: #dc2626; font-weight: 600; }
        .summary { font-size: 18px; font-weight: 600; padding: 16px; border-radius: 8px; margin-bottom: 20px; }
        .summary.ok { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .summary.fail { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Hello World</h1>
    <p>Если вы видите эту страницу - Docker-окружение работает.</p>

    <div class="summary <?= $overallOk ? 'ok' : 'fail' ?>">
        <?= $overallOk ? 'Все проверки пройдены' : 'Есть проблемы, смотрите детали ниже' ?>
    </div>

    <div class="card">
        <h2>PHP</h2>
        <div class="row"><span class="key">Версия</span><span class="val"><?= PHP_VERSION ?></span></div>
        <div class="row"><span class="key">SAPI</span><span class="val"><?= PHP_SAPI ?></span></div>
        <div class="row"><span class="key">Часовой пояс</span><span class="val"><?= date_default_timezone_get() ?></span></div>
    </div>

    <div class="card">
        <h2>Расширения</h2>
        <?php foreach ($extensionStatus as $ext => $loaded): ?>
            <div class="row">
                <span class="key"><?= htmlspecialchars($ext) ?></span>
                <span class="val <?= $loaded ? 'ok' : 'fail' ?>">
                    <?= $loaded ? 'загружено' : 'отсутствует' ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <h2>MySQL</h2>
        <div class="row">
            <span class="key">Статус</span>
            <span class="val <?= $dbStatus === 'ok' ? 'ok' : 'fail' ?>">
                <?= $dbStatus === 'ok' ? 'подключение установлено' : 'ошибка' ?>
            </span>
        </div>
        <?php if ($dbVersion !== null): ?>
            <div class="row"><span class="key">Версия</span><span class="val"><?= htmlspecialchars((string) $dbVersion) ?></span></div>
        <?php endif; ?>
        <?php if ($dbError !== null): ?>
            <div class="row">
                <span class="key">Ошибка</span>
                <span class="val fail"><?= htmlspecialchars($dbError) ?></span>
            </div>
        <?php endif; ?>
    </div>

    <p style="color:#6b7280;font-size:14px">
        Это временная диагностическая страница. Она будет заменена на настоящий front controller
        на следующем шаге.
    </p>
</div>
</body>
</html>
