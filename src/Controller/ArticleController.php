<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Service\ArticleService;
use App\Support\View;

/**
 * Страница статьи: полная информация + 3 похожих.
 */
final class ArticleController
{
    public function __construct(
        private readonly ArticleService $service,
        private readonly View $view,
    ) {
    }

    /**
     * GET /article/{slug}
     */
    public function show(Request $request, string $slug): Response
    {
        $article = $this->service->getBySlug($slug);

        // Атомарно увеличиваем просмотры и берём актуальное значение.
        $views = $this->service->registerView($article->id);

        $similar = $this->service->getSimilar($article, 3);

        // Текст экранируем и превращаем переносы строк в <br> здесь,
        // чтобы в шаблоне вывести через nofilter без риска XSS.
        $contentHtml = nl2br(htmlspecialchars($article->content, ENT_QUOTES, 'UTF-8'));

        $html = $this->view->render('article.tpl', [
            'title'        => $article->title,
            'article'      => $article,
            'views'        => $views,
            'similar'      => $similar,
            'content_html' => $contentHtml,
        ]);

        return Response::html($html);
    }
}
