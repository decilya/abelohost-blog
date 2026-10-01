<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

/**
 * Сущность не найдена.
 *
 * Бросается из сервисов и роутера. Front controller ловит это исключение
 * и отдаёт страницу 404.
 */
final class NotFoundException extends RuntimeException
{
}
