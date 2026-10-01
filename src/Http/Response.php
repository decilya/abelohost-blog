<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Ответ HTTP.
 *
 * По умолчанию - HTML со статусом 200. Заголовки можно добавлять
 * через withHeader(). Метод send() отправляет ответ клиенту.
 */
final class Response
{
    /**
     * @param int $status HTTP-статус (200, 404, 500, ...)
     * @param string $body Тело ответа
     * @param array<string, string> $headers Заголовки ответа
     */
    public function __construct(
        private int $status = 200,
        private string $body = '',
        private array $headers = ['Content-Type' => 'text/html; charset=utf-8'],
    ) {
    }

    /**
     * Создаёт HTML-ответ. Content-Type выставляется автоматически.
     */
    public static function html(string $body, int $status = 200): self
    {
        return new self($status, $body);
    }

    /**
     * Создаёт редирект. По умолчанию - временный (302).
     */
    public static function redirect(string $location, int $status = 302): self
    {
        return new self($status, '', ['Location' => $location]);
    }

    /**
     * Добавляет HTTP-заголовок. Возвращает $this для цепочки вызовов.
     */
    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Отправляет ответ клиенту.
     */
    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->body;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }
}
