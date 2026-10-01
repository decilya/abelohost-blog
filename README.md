# AbeloHost Blog

Тестовое задание: блог на чистом PHP 8.1+ с категориями и статьями.

## Быстрый старт

Требуется Docker и Docker Compose.

    cp .env.example .env
    make init

Открыть: http://localhost:8080

## Требования

- Docker 24+
- Docker Compose v2
- Make

## Команды

    make up       # поднять контейнеры
    make down     # остановить
    make logs     # логи
    make shell    # зайти в php-контейнер
    make composer # composer install внутри контейнера
    make clean    # остановить и удалить volume с MySQL

## Статус

Проект в разработке. Документация будет дополнена.
