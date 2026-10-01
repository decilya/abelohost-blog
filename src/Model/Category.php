<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Доменная модель категории.
 *
 * Иммутабельная: после создания свойства не меняются. Собирается из
 * строки БД через fromRow(). Никакой логики - только данные и хелперы
 * для представления.
 */
final class Category
{
    /**
     * @param int $id Идентификатор
     * @param string $name Название категории
     * @param string $slug URL-идентификатор для маршрута /category/{slug}
     * @param string|null $description Описание или null
     * @param string $createdAt Дата создания в формате MySQL TIMESTAMP
     */
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
     * @param array<string, mixed> $row Ассоциативный массив из PDO
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
