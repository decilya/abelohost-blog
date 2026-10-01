<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Порядок сортировки статей на странице категории.
 *
 * Единый whitelist: пользовательский ввод из query string маппится
 * на один из этих кейсов. Реальное SQL-выражение берётся из toSql(),
 * а не из пользовательского ввода - инъекция в ORDER BY невозможна.
 */
enum SortOrder: string
{
    case DateDesc  = 'date_desc';
    case DateAsc   = 'date_asc';
    case ViewsDesc = 'views_desc';
    case ViewsAsc  = 'views_asc';

    /**
     * Возвращает SQL-выражение для ORDER BY.
     *
     * Значения фиксированы, никакого пользовательского ввода.
     * Вторичная сортировка по id - для предсказуемости при равных значениях.
     */
    public function toSql(): string
    {
        return match ($this) {
            self::DateDesc  => 'a.published_at DESC, a.id DESC',
            self::DateAsc   => 'a.published_at ASC, a.id ASC',
            self::ViewsDesc => 'a.views DESC, a.id DESC',
            self::ViewsAsc  => 'a.views ASC, a.id ASC',
        };
    }

    /**
     * Человекочитаемое название для UI (селекта сортировки).
     */
    public function label(): string
    {
        return match ($this) {
            self::DateDesc  => 'Сначала новые',
            self::DateAsc   => 'Сначала старые',
            self::ViewsDesc => 'Популярные',
            self::ViewsAsc  => 'Непопулярные',
        };
    }

    /**
     * Безопасно парсит значение из query string.
     * Если значение невалидное или null - возвращает DateDesc по умолчанию.
     */
    public static function fromRequest(?string $value): self
    {
        if ($value === null) {
            return self::DateDesc;
        }

        return self::tryFrom($value) ?? self::DateDesc;
    }
}
