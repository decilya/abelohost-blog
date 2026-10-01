<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Доменная модель категории.
 *
 * Иммутабельная (readonly), собирается из строки БД через fromRow().
 */
final class Category
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly string $createdAt,
    ) {
    }

    /**
     * Создаёт модель из строки БД.
     *
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) $row['name'],
            slug: (string) $row['slug'],
            description: isset($row['description']) ? (string) $row['description'] : null,
            createdAt: (string) $row['created_at'],
        );
    }
}
