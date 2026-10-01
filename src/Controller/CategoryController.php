<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\SortOrder;
use App\Http\Request;
use App\Http\Response;
use App\Service\CategoryService;
use App\Support\View;

/**
 * Страница категории: список статей, сортировка, пагинация.
 */
final class CategoryController
{
    public function __construct(
        private readonly CategoryService $service,
        private readonly View $view,
    ) {
    }

    /**
     * GET /category/{slug}
     */
    public function show(Request $request, string $slug): Response
    {
        $data = $this->service->getCategoryPage(
            $slug,
            $request->stringQuery('sort'),
            $request->intQuery('page', 1),
        );

        $html = $this->view->render('category.tpl', [
            'title'         => $data['category']->name,
            'category'      => $data['category'],
            'articles'      => $data['articles'],
            'page'          => $data['page'],
            'totalPages'    => $data['totalPages'],
            'totalArticles' => $data['totalArticles'],
            'sort'          => $data['sort'],
            // Все варианты сортировки для переключателя в шаблоне.
            'allSorts'      => SortOrder::cases(),
        ]);

        return Response::html($html);
    }
}
