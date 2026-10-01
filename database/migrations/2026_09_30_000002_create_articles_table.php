<?php

declare(strict_types=1);

namespace Database\Migrations;

use App\Database\Migration;
use PDO;

/**
 * Создаёт таблицу articles.
 *
 * Медиум-текст под content - чтобы вместить длинные статьи с разметкой.
 * Отдельные индексы на published_at и views - по ним сортировка
 * на странице категории (см. SortOrder enum).
 */
final class CreateArticlesTable implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE TABLE articles (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                description TEXT DEFAULT NULL,
                content MEDIUMTEXT NOT NULL,
                image VARCHAR(255) DEFAULT NULL,
                views INT UNSIGNED NOT NULL DEFAULT 0,
                published_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_articles_slug (slug),
                INDEX idx_articles_published_at (published_at),
                INDEX idx_articles_views (views)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);
    }

    /**
     * {@inheritDoc}
     */
    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS articles');
    }
}
