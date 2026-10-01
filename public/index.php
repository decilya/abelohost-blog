<?php

declare(strict_types=1);

/**
 * Точка входа приложения (front controller).
 *
 * Все HTTP-запросы приходят сюда через rewrite rules Nginx. Здесь:
 *  1. Загружается Composer-автозагрузчик.
 *  2. Загружается .env через vlucas/phpdotenv.
 *  3. Читаются конфиги.
 *  4. Настраивается режим ошибок (debug/production).
 *  5. Собирается DI-контейнер.
 *  6. Создаётся роутер и регистрируются маршруты.
 *  7. Обрабатывается запрос, исключения превращаются в 404/500.
 */

use App\Controller\HelloController;
use App\Exception\NotFoundException;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Support\Container;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$root = dirname(__DIR__);

// Загружаем .env. safeLoad() не падает, если файла нет.
$dotenv = Dotenv::createImmutable($root);
$dotenv->safeLoad();

$appConfig = require $root . '/config/app.php';

// Настраиваем отображение ошибок.
if ($appConfig['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED);
}

// Собираем DI-контейнер. Пока пустой - наполним по мере появления сервисов.
$container = new Container();

// Роутер с типобезопасными обработчиками.
$router = new Router($container);
$router->get('/', [HelloController::class, 'index']);

$request = Request::fromGlobals();

try {
    $response = $router->dispatch($request);
} catch (NotFoundException $e) {
    // 404. В debug можно показать сообщение, в production - общий текст.
    $message = $appConfig['debug']
        ? htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
        : 'Страница не найдена.';

    $response = Response::html(
        "<h1>404</h1><p>{$message}</p><p><a href=\"/\">На главную</a></p>",
        404,
    );
} catch (Throwable $e) {
    // 500. Детали только в debug.
    $message = $appConfig['debug']
        ? htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
        : 'Внутренняя ошибка сервера.';

    $response = Response::html(
        "<h1>500</h1><p>{$message}</p><p><a href=\"/\">На главную</a></p>",
        500,
    );
}

$response->send();
