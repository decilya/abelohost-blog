<?php

declare(strict_types=1);

namespace Database\Migrations;

use App\Database\Migration;
use PDO;

/**
 * Создаёт таблицу categories.
 *
 * Категория блога: название, slug для URL, описание.
 * Slug уникален и индексирован - по нему строится маршрут /category/{slug}.
 */
final class CreateCategoriesTable implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE TABLE categories (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                description TEXT DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_categories_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);
    }

    /**
     * {@inheritDoc}
     */
    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS categories');
    }
}
