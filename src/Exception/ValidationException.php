<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

/**
 * Ошибка валидации входных данных.
 *
 * Хранит список ошибок по полям формы.
 * В текущем ТЗ форм нет, но класс готов для будущего использования
 * (например, при добавлении админки).
 */
final class ValidationException extends RuntimeException
{
    /**
     * @param array<string, string> $errors Карта: имя поля => текст ошибки
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
