<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use RuntimeException;

/**
 * Управляет миграциями: накат, откат, отслеживание применённых.
 *
 * Отслеживание ведётся в таблице migrations - там хранятся имена
 * применённых миграций. При запуске сканируется папка с миграциями,
 * сравнивается с этой таблицей, применяются только новые.
 *
 * Порядок применения - по имени файла (сортировка строк). Поэтому
 * файлы префиксованы таймстампом: 2026_09_30_000001_..., 2026_09_30_000002_...
 *
 * Почему миграции и сидеры не в PSR-4, а в classmap (см. composer.json):
 * PSR-4 требует, чтобы имя файла точно совпадало с именем класса
 * (CreateCategoriesTable.php), а нам нужен префикс с таймстампом, чтобы
 * гарантировать порядок применения. Classmap строит карту классов
 * сканированием содержимого папки - имена файлов для него не важны.
 * Цена: после добавления новой миграции нужно пересобрать карту через
 * `composer dump-autoload`. Это выполняет Makefile-цель после миграций
 * или CI-скрипт.
 */
final class Migrator
{
    /**
     * Карта: имя файла миграции => FQCN класса.
     *
     * @var array<string, class-string<Migration>>
     */
    private array $migrations = [];

    /**
     * @param PDO $pdo Активное соединение с БД
     * @param string $migrationsPath Абсолютный путь до папки с миграциями
     *
     * @throws RuntimeException Если класс миграции не найден
     */
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $migrationsPath,
    ) {
        $this->ensureMigrationsTable();
        $this->loadMigrations();
    }

    /**
     * Создаёт служебную таблицу migrations, если её ещё нет.
     *
     * Без неё нельзя понять, какие миграции уже применены.
     */
    private function ensureMigrationsTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /**
     * Сканирует папку миграций и формирует карту file => FQCN.
     *
     * Имя файла без расширения = ключ миграции в таблице migrations.
     * Класс должен быть в namespace Database\Migrations с именем
     * в PascalCase: create_categories_table -> CreateCategoriesTable.
     *
     * @throws RuntimeException Если файл есть, а класса с таким именем нет
     */
    private function loadMigrations(): void
    {
        $files = glob($this->migrationsPath . '/*.php') ?: [];
        sort($files, SORT_STRING);

        foreach ($files as $file) {
            $name = basename($file, '.php');
            $className = $this->filenameToClassName($name);
            $fqcn = 'Database\\Migrations\\' . $className;

            if (!class_exists($fqcn)) {
                throw new RuntimeException("Класс миграции не найден: {$fqcn}");
            }

            $this->migrations[$name] = $fqcn;
        }
    }

    /**
     * Преобразует имя файла в имя класса.
     *
     * 2026_09_30_000001_create_categories_table
     *   -> CreateCategoriesTable
     *
     * Первые три части (год_месяц_день) и четвёртая (порядковый номер)
     * отрезаются. Остальные склеиваются в PascalCase.
     */
    private function filenameToClassName(string $filename): string
    {
        $parts = explode('_', $filename);
        // [2026, 09, 30, 000001, create, categories, table]
        $parts = array_slice($parts, 4);
        // [create, categories, table] -> CreateCategoriesTable

        return str_replace(' ', '', ucwords(implode(' ', $parts)));
    }

    /**
     * Применяет все неприменённые миграции.
     *
     * Возвращает список имён применённых миграций в этом вызове.
     * Если ничего не применилось - пустой массив.
     *
     * @return string[]
     */
    public function migrate(): array
    {
        $applied = $this->getAppliedMigrations();
        $executed = [];

        foreach ($this->migrations as $name => $className) {
            if (in_array($name, $applied, true)) {
                continue;
            }

            /** @var Migration $migration */
            $migration = new $className();
            $migration->up($this->pdo);

            $stmt = $this->pdo->prepare('INSERT INTO migrations (migration) VALUES (?)');
            $stmt->execute([$name]);

            $executed[] = $name;
        }

        return $executed;
    }

    /**
     * Откатывает N последних применённых миграций.
     *
     * Порядок обратный порядку применения: последняя применённая
     * откатывается первой. Это важно, если миграции зависят друг от друга.
     *
     * @param int $steps Сколько миграций откатить (по умолчанию одну)
     * @return string[] Имена откаченных миграций
     */
    public function rollback(int $steps = 1): array
    {
        $applied = $this->getAppliedMigrations();

        // Берём последние N и разворачиваем: сначала откатываем самую свежую.
        $toRollback = array_slice(array_reverse($applied), 0, $steps);
        $rolledBack = [];

        foreach ($toRollback as $name) {
            if (!isset($this->migrations[$name])) {
                continue;
            }

            $className = $this->migrations[$name];
            /** @var Migration $migration */
            $migration = new $className();
            $migration->down($this->pdo);

            $stmt = $this->pdo->prepare('DELETE FROM migrations WHERE migration = ?');
            $stmt->execute([$name]);

            $rolledBack[] = $name;
        }

        return $rolledBack;
    }

    /**
     * Возвращает список применённых миграций в порядке применения.
     *
     * @return string[]
     */
    private function getAppliedMigrations(): array
    {
        return $this->pdo
            ->query('SELECT migration FROM migrations ORDER BY id ASC')
            ->fetchAll(PDO::FETCH_COLUMN);
    }
}
