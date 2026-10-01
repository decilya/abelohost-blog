<?php

declare(strict_types=1);

namespace Database\Migrations;

use App\Database\Migration;
use PDO;

/**
 * Создаёт таблицу article_category - связь many-to-many.
 *
 * Одна статья может быть в нескольких категориях, в одной категории -
 * много статей. Составной первичный ключ не даёт создать дубликат связи.
 *
 * ON DELETE CASCADE: удаление статьи или категории автоматически
 * удаляет связанные записи. Удобно для сидинга и чистки.
 */
final class CreateArticleCategoryTable implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE TABLE article_category (
                article_id INT UNSIGNED NOT NULL,
                category_id INT UNSIGNED NOT NULL,
                PRIMARY KEY (article_id, category_id),
                CONSTRAINT fk_ac_article
                    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
                CONSTRAINT fk_ac_category
                    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
                INDEX idx_ac_category (category_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);
    }

    /**
     * {@inheritDoc}
     */
    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS article_category');
    }
}
