<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Article;
use App\Model\Category;
use App\Repository\ArticleRepositoryInterface;
use App\Repository\CategoryRepositoryInterface;

/**
 * Бизнес-логика главной страницы.
 *
 * Собирает категории и привязывает к каждой N последних статей.
 * Группирует плоский результат оконной функции из ArticleRepository.
 */
final class HomeService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
        private readonly ArticleRepositoryInterface $articles,
    ) {
    }

    /**
     * Возвращает категории с последними статьями.
     *
     * @param int $perCategory Сколько статей показывать в каждой категории
     * @return array<int, array{category: Category, articles: Article[]}>
     */
    public function getCategoriesWithLatestArticles(int $perCategory = 3): array
    {
        $categories = $this->categories->findAllWithArticles();

        if ($categories === []) {
            return [];
        }

        // Индексируем категории по id для быстрого поиска при группировке.
        $byId = [];
        foreach ($categories as $category) {
            $byId[$category->id] = $category;
        }

        // Плоский список: [(category_id, Article), ...]
        $latest = $this->articles->getLatestPerCategory($perCategory);

        $grouped = [];
        foreach ($latest as $item) {
            $cid = $item['category_id'];
            if (!isset($byId[$cid])) {
                continue;
            }
            $grouped[$cid][] = $item['article'];
        }

        // Собираем результат в порядке исходного списка категорий.
        $result = [];
        foreach ($categories as $category) {
            $result[] = [
                'category' => $category,
                'articles' => $grouped[$category->id] ?? [],
            ];
        }

        return $result;
    }
}
