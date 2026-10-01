<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Доменная модель статьи.
 *
 * Иммутабельная. Хранит связанные категории в свойстве $categories.
 */
final class Article
{
    /**
     * @param Category[] $categories
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
     * @param array<string, mixed> $row
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
     * Placeholder - data URI, чтобы не зависеть от внешних сервисов
     * и не отдавать 404, если изображения нет.
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
     * Дата публикации в формате d.m.Y.
     */
    public function formattedDate(): string
    {
        return date('d.m.Y', strtotime($this->publishedAt));
    }

    /**
     * Просмотры в компактном виде: 1234 -> 1.2K.
     */
    public function formattedViews(): string
    {
        return $this->views >= 1000
            ? round($this->views / 1000, 1) . 'K'
            : (string) $this->views;
    }
}
