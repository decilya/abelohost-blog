<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Service\HomeService;
use App\Support\View;

/**
 * Главная страница: категории с 3 последними статьями.
 */
final class HomeController
{
    public function __construct(
        private readonly HomeService $service,
        private readonly View $view,
    ) {
    }

    /**
     * GET /
     */
    public function index(Request $request): Response
    {
        $groups = $this->service->getCategoriesWithLatestArticles(3);

        $html = $this->view->render('home.tpl', [
            'title'  => 'Все категории',
            'groups' => $groups,
        ]);

        return Response::html($html);
    }
}
