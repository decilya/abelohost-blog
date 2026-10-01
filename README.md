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
    make migrate  # применить миграции
    make rollback # откатить последнюю миграцию
    make clean    # остановить и удалить volume с MySQL

## Автозагрузка: PSR-4 + classmap

Весь код приложения (`App\`) в `src/` подключён через **PSR-4** -
стандартный и предсказуемый способ: имя класса совпадает с путём файла.

Миграции (`Database\Migrations\`) и сидеры (`Database\Seeders\`)
подключены через **classmap**. Причина: PSR-4 требует точного совпадения
имени файла с именем класса (`CreateCategoriesTable.php`), а нам нужен
префикс-таймстамп для гарантии порядка применения:

    2026_09_30_000001_create_categories_table.php
    2026_09_30_000002_create_articles_table.php
    2026_09_30_000003_create_article_category_table.php

Classmap строит карту классов сканированием содержимого папки - имена
файлов для него не важны. Цена решения: после добавления новой миграции
или сидера нужно пересобрать карту:

    docker compose exec --user root php composer dump-autoload -o
    sudo chown -R $(id -u):$(id -g) vendor composer.lock

Альтернатива - писать имена файлов по PSR-4 без таймстампа и полагаться
на лексикографическую сортировку имён. Но тогда добавление миграции
"в середину" истории сломает порядок, а с таймстампом порядок фиксирован
навсегда. Это стандартный подход - так делают Laravel, Symfony, Phinx.

## Статус

Проект в разработке. Документация будет дополнена.
