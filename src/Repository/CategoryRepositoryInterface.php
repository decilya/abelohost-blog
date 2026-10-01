<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Category;

/**
 * Контракт репозитория категорий.
 *
 * Определяет операции для работы с данными категорий.
 * Реализация использует PDO. Разделение на интерфейс и класс
 * позволяет подменять реализацию (например, на кэширующую)
 * без изменения бизнес-логики в сервисах (Dependency Inversion).
 */
interface CategoryRepositoryInterface
{
    /**
     * Возвращает все категории, в которых есть хотя бы одна статья.
     *
     * Используется на главной странице: показываем только категории с контентом.
     *
     * @return Category[]
     */
    public function findAllWithArticles(): array;

    /**
     * Находит категорию по slug.
     *
     * @param string $slug URL-идентификатор
     * @return Category|null
     */
    public function findBySlug(string $slug): ?Category;

    /**
     * Возвращает количество статей в категории.
     *
     * @param int $categoryId ID категории
     * @return int
     */
    public function countArticles(int $categoryId): int;
}
