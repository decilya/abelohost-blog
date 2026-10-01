<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Connection;
use App\Model\Category;
use PDO;

/**
 * Реализация репозитория категорий на PDO.
 *
 * Все запросы используют подготовленные выражения (prepared statements).
 */
final class CategoryRepository implements CategoryRepositoryInterface
{
    private PDO $pdo;

    public function __construct(Connection $connection)
    {
        $this->pdo = $connection->pdo();
    }

    /**
     * {@inheritDoc}
     *
     * Использует EXISTS вместо JOIN + GROUP BY для лучшей производительности
     * на больших таблицах.
     */
    public function findAllWithArticles(): array
    {
        $sql = <<<'SQL'
            SELECT c.*
            FROM categories c
            WHERE EXISTS (
                SELECT 1 FROM article_category ac WHERE ac.category_id = c.id
            )
            ORDER BY c.name ASC
        SQL;

        $rows = $this->pdo->query($sql)->fetchAll();

        return array_map(Category::fromRow(...), $rows);
    }

    /**
     * {@inheritDoc}
     */
    public function findBySlug(string $slug): ?Category
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();

        return $row === false ? null : Category::fromRow($row);
    }

    /**
     * {@inheritDoc}
     */
    public function countArticles(int $categoryId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM article_category WHERE category_id = ?'
        );
        $stmt->execute([$categoryId]);

        return (int) $stmt->fetchColumn();
    }
}
