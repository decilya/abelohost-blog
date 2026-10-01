<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\NotFoundException;
use App\Model\Article;
use App\Repository\ArticleRepositoryInterface;

/**
 * Бизнес-логика страницы статьи.
 */
final class ArticleService
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articles,
    ) {
    }

    /**
     * Возвращает статью по slug вместе с категориями.
     *
     * @throws NotFoundException Если статья не найдена
     */
    public function getBySlug(string $slug): Article
    {
        $article = $this->articles->findBySlugWithCategories($slug);
        if ($article === null) {
            throw new NotFoundException("Статья не найдена: {$slug}");
        }

        return $article;
    }

    /**
     * Увеличивает счётчик просмотров и возвращает актуальное значение.
     */
    public function registerView(int $articleId): int
    {
        return $this->articles->incrementViews($articleId);
    }

    /**
     * Возвращает похожие статьи (по общим категориям).
     *
     * @return Article[]
     */
    public function getSimilar(Article $article, int $limit = 3): array
    {
        return $this->articles->findSimilar($article->id, $limit);
    }
}
