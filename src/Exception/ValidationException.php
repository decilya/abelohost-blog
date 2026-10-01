<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

/**
 * Ошибка валидации входных данных.
 *
 * Хранит карту «имя поля => текст ошибки». Пригодится, когда появится
 * форма создания статьи или загрузка изображения: можно вернуть
 * пользователю все ошибки сразу, а не по одной.
 *
 * В HTTP-контексте соответствует статусу 422.
 */
final class ValidationException extends RuntimeException
{
    /**
     * @param array<string, string> $errors Карта: имя поля => сообщение
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Ошибка валидации данных');
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
