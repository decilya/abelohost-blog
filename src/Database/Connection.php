<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;

/**
 * Обёртка над PDO с ленивым подключением.
 *
 * Единственное место в проекте, где создаётся соединение с MySQL.
 * Синглтон-поведение обеспечивает DI-контейнер: один Connection на запрос.
 *
 * Подключение создаётся при первом обращении к pdo(), а не в конструкторе.
 * Это позволяет контейнеру создать объект, даже если класс, который его
 * запросил, не будет обращаться к БД в текущем запросе.
 */
final class Connection
{
    private ?PDO $pdo = null;

    /**
     * @param array{
     *     host: string,
     *     port: int,
     *     database: string,
     *     username: string,
     *     password: string,
     *     charset: string
     * } $config Конфигурация из config/database.php
     */
    public function __construct(private readonly array $config)
    {
    }

    /**
     * Возвращает PDO-соединение, создавая его при первом обращении.
     *
     * @throws PDOException Если соединение не удалось установить
     */
    public function pdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = $this->createPdo();
        }

        return $this->pdo;
    }

    /**
     * Создаёт новое PDO-соединение.
     *
     * ERRMODE_EXCEPTION - ошибки летят исключениями, а не warning'ами.
     * EMULATE_PREPARES = false - настоящие prepared statements на стороне
     * MySQL вместо эмуляции на стороне PHP. Безопаснее и корректнее с типами.
     *
     * @throws PDOException
     */
    private function createPdo(): PDO
    {
        $c = $this->config;
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $c['host'],
            $c['port'],
            $c['database'],
            $c['charset'],
        );

        return new PDO($dsn, $c['username'], $c['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]);
    }
}
