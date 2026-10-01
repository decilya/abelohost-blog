<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Обёртка вокруг суперглобальных массивов.
 *
 * Собирается один раз в точке входа и передаётся в Router. Позволяет
 * контроллерам и сервисам работать с запросом через явный интерфейс,
 * а не через $_GET, $_POST напрямую.
 */
final class Request
{
    /**
     * @param string $method HTTP-метод в верхнем регистре
     * @param string $path Путь без query string, всегда с ведущим слешем
     * @param array<string, mixed> $query $_GET
     * @param array<string, mixed> $post $_POST
     * @param array<string, mixed> $files $_FILES
     * @param array<string, mixed> $server $_SERVER
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $post,
        public readonly array $files,
        public readonly array $server,
    ) {
    }

    /**
     * Собирает Request из суперглобальных массивов.
     *
     * Нормализует путь: убирает trailing slash (кроме корня), чтобы
     * /category/php и /category/php/ были одним и тем же маршрутом.
     */
    public static function fromGlobals(): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return new self(
            method: strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            path: $path,
            query: $_GET,
            post: $_POST,
            files: $_FILES,
            server: $_SERVER,
        );
    }

    /**
     * Возвращает значение из query string или default.
     *
     * @param mixed $default
     * @return mixed
     */
    public function queryParam(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Возвращает значение из тела POST-запроса или default.
     *
     * @param mixed $default
     * @return mixed
     */
    public function postParam(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Целочисленный параметр query string с приведением типа.
     * Невалидное значение (не число) заменяется на default.
     */
    public function intQuery(string $key, int $default = 0): int
    {
        $value = $this->query[$key] ?? null;

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * Строковый параметр query string или null, если его нет или он не строка.
     */
    public function stringQuery(string $key): ?string
    {
        $value = $this->query[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * Возвращает загруженный файл по имени поля формы.
     *
     * @return array<string, mixed>|null
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * CSRF-токен из POST-запроса.
     */
    public function csrfToken(): ?string
    {
        $token = $this->post['_token'] ?? null;

        return is_string($token) ? $token : null;
    }
}
