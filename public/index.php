<?php

declare(strict_types=1);

use App\Controller\ArticleController;
use App\Controller\CategoryController;
use App\Controller\HomeController;
use App\Database\Connection;
use App\Exception\NotFoundException;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Repository\ArticleRepository;
use App\Repository\ArticleRepositoryInterface;
use App\Repository\CategoryRepository;
use App\Repository\CategoryRepositoryInterface;
use App\Support\Container;
use App\Support\View;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$root = dirname(__DIR__);

$dotenv = Dotenv::createImmutable($root);
$dotenv->safeLoad();

$appConfig = require $root . '/config/app.php';
$dbConfig  = require $root . '/config/database.php';

if ($appConfig['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED);
}

// DI-контейнер: синглтоны инфраструктуры + привязка интерфейсов к реализациям.
$container = new Container();

$container->singleton(Connection::class, static fn () => new Connection($dbConfig));
$container->singleton(View::class, static fn () => new View($appConfig));

$container->singleton(
    CategoryRepositoryInterface::class,
    static fn (Container $c) => new CategoryRepository($c->get(Connection::class)),
);
$container->singleton(
    ArticleRepositoryInterface::class,
    static fn (Container $c) => new ArticleRepository($c->get(Connection::class)),
);

// Сервисы и контроллеры контейнер собирает сам через рефлексию.
$router = new Router($container);

$router->get('/', [HomeController::class, 'index']);
$router->get('/category/{slug}', [CategoryController::class, 'show']);
$router->get('/article/{slug}', [ArticleController::class, 'show']);

$request = Request::fromGlobals();

try {
    $response = $router->dispatch($request);
} catch (NotFoundException $e) {
    $view = $container->get(View::class);
    $body = $view->render('404.tpl', ['title' => '404']);
    $response = Response::html($body, 404);
} catch (Throwable $e) {
    $message = $appConfig['debug']
        ? htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
        : 'Внутренняя ошибка сервера.';

    try {
        $view = $container->get(View::class);
        $body = $view->render('500.tpl', ['title' => '500', 'error_message' => $message]);
    } catch (Throwable) {
        // Если упал сам Smarty - отдаём голый HTML.
        $body = "<h1>500</h1><p>{$message}</p><p><a href=\"/\">На главную</a></p>";
    }
    $response = Response::html($body, 500);
}

$response->send();
