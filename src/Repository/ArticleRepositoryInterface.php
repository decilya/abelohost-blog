<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\SortOrder;
use App\Model\Article;

/**
 * Контракт репозитория статей.
 */
interface ArticleRepositoryInterface
{
    /**
     * Возвращает по N последних статей для каждой категории одним запросом.
     *
     * Использует оконную функцию ROW_NUMBER() OVER (PARTITION BY category_id).
     * Возвращает плоский список, который сервис должен сгруппировать.
     * Это полностью избавляет от проблемы N+1 запросов на главной странице.
     *
     * @param int $perCategory Сколько статей на категорию
     * @return array<int, array{category_id: int, article: Article}>
     */
    public function getLatestPerCategory(int $perCategory = 3): array;

    /**
     * Возвращает статьи категории с пагинацией и сортировкой.
     *
     * @param int $categoryId ID категории
     * @param SortOrder $sortOrder Порядок сортировки (whitelist)
     * @param int $limit Лимит записей
     * @param int $offset Смещение
     * @return Article[]
     */
    public function findPaginatedByCategory(
        int $categoryId,
        SortOrder $sortOrder,
        int $limit,
        int $offset,
    ): array;

    /**
     * Находит статью по slug вместе с её категориями.
     *
     * @param string $slug URL-идентификатор
     * @return Article|null
     */
    public function findBySlugWithCategories(string $slug): ?Article;

    /**
     * Атомарно увеличивает счётчик просмотров.
     *
     * Один UPDATE без предварительного SELECT предотвращает race conditions
     * при одновременных запросах.
     *
     * @param int $articleId ID статьи
     * @return int Новое значение счётчика
     */
    public function incrementViews(int $articleId): int;

    /**
     * Возвращает до $limit похожих статей по общим категориям.
     *
     * @param int $articleId ID текущей статьи (исключается из результатов)
     * @param int $limit Максимальное количество похожих статей
     * @return Article[]
     */
    public function findSimilar(int $articleId, int $limit = 3): array;
}
