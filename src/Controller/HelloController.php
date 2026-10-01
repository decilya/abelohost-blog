<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;

/**
 * Временный контроллер для проверки HTTP-слоя.
 *
 * Возвращает простой HTML, чтобы убедиться, что Router, Request,
 * Response и DI-контейнер работают корректно. Будет удалён на шаге,
 * когда появятся настоящие контроллеры (Home, Category, Article).
 */
final class HelloController
{
    /**
     * GET /
     */
    public function index(Request $request): Response
    {
        $html = <<<'HTML'
            <!DOCTYPE html>
            <html lang="ru">
            <head>
                <meta charset="utf-8">
                <title>HTTP layer works</title>
                <style>
                    body { font-family: sans-serif; padding: 40px; background: #f7f7f8; color: #1f2328; }
                    h1 { color: #2563eb; }
                    code { background: #eee; padding: 2px 6px; border-radius: 4px; }
                </style>
            </head>
            <body>
                <h1>HTTP layer works</h1>
                <p>Router, Request, Response, DI Container - всё подключено и работает.</p>
                <p>Следующий шаг - <code>config</code> подключён, добавляем миграции и БД.</p>
            </body>
            </html>
            HTML;

        return Response::html($html);
    }
}
