<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Доменная модель статьи.
 *
 * Иммутабельная. Отдельный метод withCategories() возвращает копию
 * с прикреплёнными категориями - вместо мутации поля categories.
 *
 * Содержит хелперы представления: URL изображения, форматирование даты
 * и просмотров. Это позволяет шаблонам оставаться лаконичными.
 */
final class Article
{
    /**
     * @param int $id Идентификатор
     * @param string $title Заголовок
     * @param string $slug URL-идентификатор для маршрута /article/{slug}
     * @param string|null $description Краткое описание
     * @param string $content Полный текст статьи
     * @param string|null $image Имя файла в public/uploads или null
     * @param int $views Количество просмотров
     * @param string $publishedAt Дата публикации
     * @param string $createdAt Дата создания
     * @param string $updatedAt Дата последнего обновления
     * @param Category[] $categories Категории статьи (по умолчанию пусто)
     */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly string $content,
        public readonly ?string $image,
        public readonly int $views,
        public readonly string $publishedAt,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly array $categories = [],
    ) {
    }

    /**
     * Создаёт модель из строки БД без категорий.
     *
     * Категории прикрепляются отдельным запросом через withCategories().
     *
     * @param array<string, mixed> $row Ассоциативный массив из PDO
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            title: (string) $row['title'],
            slug: (string) $row['slug'],
            description: isset($row['description']) ? (string) $row['description'] : null,
            content: (string) $row['content'],
            image: isset($row['image']) ? (string) $row['image'] : null,
            views: (int) $row['views'],
            publishedAt: (string) $row['published_at'],
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
        );
    }

    /**
     * Возвращает копию статьи с прикреплёнными категориями.
     *
     * Иммутабельно: исходный объект не меняется. Это удобно, когда
     * статьи кэшируются или передаются между слоями.
     *
     * @param Category[] $categories
     */
    public function withCategories(array $categories): self
    {
        return new self(
            id: $this->id,
            title: $this->title,
            slug: $this->slug,
            description: $this->description,
            content: $this->content,
            image: $this->image,
            views: $this->views,
            publishedAt: $this->publishedAt,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
            categories: $categories,
        );
    }

    /**
     * Есть ли у статьи изображение.
     */
    public function hasImage(): bool
    {
        return $this->image !== null && $this->image !== '';
    }

    /**
     * URL изображения или встроенный SVG-placeholder.
     *
     * Placeholder - data URI: не тянет внешние ресурсы, не даёт 404,
     * не требует отдельной картинки-заглушки в репозитории.
     */
    public function imageUrl(): string
    {
        if ($this->hasImage()) {
            return '/uploads/' . $this->image;
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="450">'
            . '<rect fill="#1c2128" width="800" height="450"/>'
            . '<text fill="#7d8590" font-family="sans-serif" font-size="20" '
            . 'x="50%" y="50%" text-anchor="middle" dy=".3em">Нет изображения</text>'
            . '</svg>';

        return 'data:image/svg+xml,' . rawurlencode($svg);
    }

    /**
     * Дата публикации в формате d.m.Y для отображения.
     */
    public function formattedDate(): string
    {
        return date('d.m.Y', strtotime($this->publishedAt));
    }

    /**
     * Просмотры в компактном виде: 1234 -> 1.2K, 999 -> 999.
     */
    public function formattedViews(): string
    {
        return $this->views >= 1000
            ? round($this->views / 1000, 1) . 'K'
            : (string) $this->views;
    }
}
