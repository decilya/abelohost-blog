<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\SortOrder;
use App\Exception\NotFoundException;
use App\Model\Article;
use App\Model\Category;
use App\Repository\ArticleRepositoryInterface;
use App\Repository\CategoryRepositoryInterface;

/**
 * Бизнес-логика страницы категории: сортировка, пагинация.
 */
final class CategoryService
{
    private const PER_PAGE = 6;

    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
        private readonly ArticleRepositoryInterface $articles,
    ) {
    }

    /**
     * Возвращает данные для страницы категории.
     *
     * @param string $slug Slug категории
     * @param string|null $sort Пользовательский ввод из query string
     * @param int $page Номер страницы, начиная с 1
     * @return array{
     *     category: Category,
     *     articles: Article[],
     *     page: int,
     *     totalPages: int,
     *     totalArticles: int,
     *     sort: SortOrder
     * }
     * @throws NotFoundException Если категория не найдена
     */
    public function getCategoryPage(string $slug, ?string $sort, int $page): array
    {
        $category = $this->categories->findBySlug($slug);
        if ($category === null) {
            throw new NotFoundException("Категория не найдена: {$slug}");
        }

        $sortOrder = SortOrder::fromRequest($sort);

        $page = max(1, $page);
        $total = $this->categories->countArticles($category->id);
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * self::PER_PAGE;

        $articles = $this->articles->findPaginatedByCategory(
            $category->id,
            $sortOrder,
            self::PER_PAGE,
            $offset,
        );

        return [
            'category'      => $category,
            'articles'      => $articles,
            'page'          => $page,
            'totalPages'    => $totalPages,
            'totalArticles' => $total,
            'sort'          => $sortOrder,
        ];
    }
}
