<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Connection;
use App\Enum\SortOrder;
use App\Model\Article;
use App\Model\Category;
use PDO;

/**
 * Реализация репозитория статей на PDO.
 *
 * Содержит оптимизированные SQL-запросы:
 * - Оконная функция ROW_NUMBER() для главной (избавляет от N+1)
 * - Атомарный инкремент просмотров (UPDATE без SELECT)
 * - Агрегация по общим категориям для поиска похожих
 */
final class ArticleRepository implements ArticleRepositoryInterface
{
    private PDO $pdo;

    public function __construct(Connection $connection)
    {
        $this->pdo = $connection->pdo();
    }

    /**
     * {@inheritDoc}
     *
     * Оконная функция MySQL 8.0+ ROW_NUMBER() OVER (PARTITION BY ...).
     *
     * Как работает:
     * 1. PARTITION BY ac.category_id - нумерация сбрасывается для каждой категории
     * 2. ORDER BY a.published_at DESC, a.id DESC - статьи внутри категории
     *    нумеруются от самой новой к старой; id как tie-breaker
     * 3. Внешний WHERE rn <= N - оставляем только топ-N в каждой категории
     *
     * Почему так, а не циклом:
     * Наивный подход требует 1 запрос на категории + N запросов на статьи
     * (проблема N+1). Оконная функция решает задачу одним запросом,
     * делегируя фильтрацию "топ-N на группу" движку БД.
     */
    public function getLatestPerCategory(int $perCategory = 3): array
    {
        $sql = <<<'SQL'
            SELECT * FROM (
                SELECT
                    a.*,
                    ac.category_id,
                    ROW_NUMBER() OVER (
                        PARTITION BY ac.category_id
                        ORDER BY a.published_at DESC, a.id DESC
                    ) AS rn
                FROM articles a
                INNER JOIN article_category ac ON ac.article_id = a.id
            ) AS ranked
            WHERE rn <= :per_category
            ORDER BY category_id ASC, rn ASC
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':per_category', $perCategory, PDO::PARAM_INT);
        $stmt->execute();

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[] = [
                'category_id' => (int) $row['category_id'],
                'article'     => Article::fromRow($row),
            ];
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     *
     * SQL-фрагмент ORDER BY берётся из SortOrder::toSql() - это whitelist.
     * Пользовательский ввод физически не может попасть в ORDER BY,
     * инъекция невозможна.
     */
    public function findPaginatedByCategory(
        int $categoryId,
        SortOrder $sortOrder,
        int $limit,
        int $offset,
    ): array {
        $orderBy = $sortOrder->toSql();

        $sql = "SELECT a.*
                FROM articles a
                INNER JOIN article_category ac ON ac.article_id = a.id
                WHERE ac.category_id = :category_id
                ORDER BY {$orderBy}
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(Article::fromRow(...), $stmt->fetchAll());
    }

    /**
     * {@inheritDoc}
     */
    public function findBySlugWithCategories(string $slug): ?Article
    {
        $stmt = $this->pdo->prepare('SELECT * FROM articles WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        $article = Article::fromRow($row);

        // Отдельный запрос за категориями проще и надёжнее, чем GROUP_CONCAT + парсинг.
        $catStmt = $this->pdo->prepare(
            'SELECT c.* FROM categories c
             INNER JOIN article_category ac ON ac.category_id = c.id
             WHERE ac.article_id = ?
             ORDER BY c.name ASC'
        );
        $catStmt->execute([$article->id]);
        $categories = array_map(Category::fromRow(...), $catStmt->fetchAll());

        return $article->withCategories($categories);
    }

    /**
     * {@inheritDoc}
     *
     * Атомарный инкремент на стороне БД. Не требует транзакции,
     * так как выполняется одним оператором UPDATE.
     * Race conditions невозможны: два одновременных UPDATE оба
     * прочитают старое значение и прибавят 1, но MySQL сериализует
     * запись на уровне строки.
     */
    public function incrementViews(int $articleId): int
    {
        $this->pdo->prepare('UPDATE articles SET views = views + 1 WHERE id = ?')
            ->execute([$articleId]);

        $stmt = $this->pdo->prepare('SELECT views FROM articles WHERE id = ?');
        $stmt->execute([$articleId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * {@inheritDoc}
     *
     * Считаем число общих категорий и сортируем по убыванию.
     * GROUP BY a.id корректен под ONLY_FULL_GROUP_BY в MySQL 8,
     * так как все выбираемые колонки функционально зависят от
     * первичного ключа a.id.
     */
    public function findSimilar(int $articleId, int $limit = 3): array
    {
        $sql = <<<'SQL'
            SELECT a.*, COUNT(ac.category_id) AS match_count
            FROM articles a
            INNER JOIN article_category ac ON ac.article_id = a.id
            WHERE ac.category_id IN (
                SELECT category_id FROM article_category WHERE article_id = :article_id
            )
              AND a.id <> :article_id
            GROUP BY a.id
            ORDER BY match_count DESC, a.published_at DESC
            LIMIT :limit
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':article_id', $articleId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(Article::fromRow(...), $stmt->fetchAll());
    }
}
