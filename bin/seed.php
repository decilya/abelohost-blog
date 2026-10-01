<?php

declare(strict_types=1);

/**
 * CLI-скрипт наполнения БД тестовыми данными.
 *
 * Использование:
 *   php bin/seed.php              - наполнить БД
 *   php bin/seed.php --clear      - только очистить таблицы
 *
 * Помимо записей в БД генерирует SVG-обложки в public/uploads/,
 * чтобы карточки статей были с реальными изображениями.
 * Все операции в одной транзакции.
 */

use App\Database\Connection;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$config = require dirname(__DIR__) . '/config/database.php';
$pdo = (new Connection($config))->pdo();

/**
 * Транслит кириллицы + нормализация для URL.
 *
 * "Оконные функции в MySQL" -> "okonnye-funkcii-v-mysql"
 */
function slugify(string $text): string
{
    $map = [
        'а' => 'a',  'б' => 'b',  'в' => 'v',  'г' => 'g',  'д' => 'd',
        'е' => 'e',  'ё' => 'yo', 'ж' => 'zh', 'з' => 'z',  'и' => 'i',
        'й' => 'y',  'к' => 'k',  'л' => 'l',  'м' => 'm',  'н' => 'n',
        'о' => 'o',  'п' => 'p',  'р' => 'r',  'с' => 's',  'т' => 't',
        'у' => 'u',  'ф' => 'f',  'х' => 'h',  'ц' => 'ts', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'sch','ъ' => '',   'ы' => 'y',  'ь' => '',
        'э' => 'e',  'ю' => 'yu', 'я' => 'ya',
        ' ' => '-',  '_' => '-',
    ];

    $text = mb_strtolower($text, 'UTF-8');
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9\-]/', '', $text);
    $text = preg_replace('/-+/', '-', $text);

    return trim($text, '-');
}

/**
 * Генерирует SVG-обложку: цветной фон + заголовок.
 * Файл лёгкий (~1 КБ), рисуется без GD.
 */
function makePlaceholderSvg(string $title, string $bg): string
{
    $safe = htmlspecialchars(mb_substr($title, 0, 45), ENT_QUOTES, 'UTF-8');

    return '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="450" viewBox="0 0 800 450">'
        . '<rect width="800" height="450" fill="' . $bg . '"/>'
        . '<circle cx="700" cy="70" r="130" fill="rgba(255,255,255,0.08)"/>'
        . '<circle cx="80" cy="410" r="90" fill="rgba(255,255,255,0.06)"/>'
        . '<text x="40" y="230" fill="#ffffff" font-family="sans-serif" '
        . 'font-size="28" font-weight="bold">' . $safe . '</text>'
        . '</svg>';
}

// Палитра обложек
$palette = ['#2563eb', '#7c3aed', '#db2777', '#ea580c', '#16a34a', '#0891b2'];

$uploadDir = dirname(__DIR__) . '/public/uploads';

$categories = [
    ['name' => 'PHP',         'description' => 'Всё о PHP: от основ языка до продвинутых тем и best practices.'],
    ['name' => 'JavaScript',  'description' => 'Frontend-разработка, Node.js, современный JS-стек.'],
    ['name' => 'Базы данных', 'description' => 'MySQL, PostgreSQL, индексы, оптимизация запросов.'],
    ['name' => 'DevOps',      'description' => 'Docker, CI/CD, деплой и инфраструктура.'],
    ['name' => 'Архитектура', 'description' => 'Паттерны проектирования, SOLID, чистый код.'],
    ['name' => 'Безопасность','description' => 'XSS, CSRF, SQL-инъекции и другие угрозы.'],
];

$articles = [
    [
        'title' => 'Оконные функции в MySQL 8.0: ROW_NUMBER, RANK и другие',
        'description' => 'Как использовать оконные функции для сложных аналитических запросов без GROUP BY.',
        'content' => 'Оконные функции — мощный инструмент MySQL 8.0+ для аналитических запросов. Они позволяют выполнять вычисления над "окном" строк, связанных с текущей строкой.' . "\n\n" .
            'Основные функции:' . "\n" .
            '- ROW_NUMBER() — нумерация строк в окне' . "\n" .
            '- RANK() — ранжирование с пропусками' . "\n" .
            '- DENSE_RANK() — ранжирование без пропусков' . "\n" .
            '- LAG() и LEAD() — доступ к предыдущей/следующей строке' . "\n\n" .
            'Типичный кейс: получить топ-N записей в каждой группе без цикла с N+1 запросами.',
        'views' => 1543, 'image' => 'mysql-window.svg', 'daysAgo' => 2,
        'categories' => ['Базы данных'],
    ],
    [
        'title' => 'Dependency Injection на чистом PHP',
        'description' => 'Пишем свой DI-контейнер с автовайрингом через рефлексию за 150 строк кода.',
        'content' => 'DI-контейнер — это объект, который создаёт другие объекты, автоматически разрешая их зависимости.' . "\n\n" .
            'Преимущества:' . "\n" .
            '- Зависимости явные — видны в конструкторе' . "\n" .
            '- Легко тестировать — можно подсунуть моки' . "\n" .
            '- Соблюдение принципа Dependency Inversion из SOLID' . "\n\n" .
            'Автовайринг работает через ReflectionClass: контейнер анализирует конструктор, смотрит на type-hint каждого параметра и рекурсивно создаёт нужные объекты.',
        'views' => 892, 'image' => null, 'daysAgo' => 5,
        'categories' => ['PHP', 'Архитектура'],
    ],
    [
        'title' => 'Почему prepared statements защищают от SQL-инъекций',
        'description' => 'Разбираем, как именно работают prepared statements и почему они безопаснее конкатенации строк.',
        'content' => 'SQL-инъекция — одна из самых распространённых уязвимостей. Prepared statements решают её на уровне протокола MySQL.' . "\n\n" .
            'Когда вы вызываете prepare(), MySQL парсит SQL и строит план выполнения. Параметры (?) заменяются плейсхолдерами. При execute() значения передаются отдельно — они никогда не попадают в SQL-парсер.' . "\n\n" .
            'Важно: эмуляция prepared statements (ATTR_EMULATE_PREPARES = true) выполняет подстановку на стороне PHP и менее безопасна. Всегда используйте false.',
        'views' => 2105, 'image' => 'sql-injection.svg', 'daysAgo' => 8,
        'categories' => ['Безопасность', 'Базы данных', 'PHP'],
    ],
    [
        'title' => 'Docker для PHP-разработчика: от Dockerfile до compose',
        'description' => 'Настраиваем окружение разработки: PHP-FPM, Nginx, MySQL в изолированных контейнерах.',
        'content' => 'Docker решает главную проблему PHP-разработки: "у меня работает, а у тебя нет".' . "\n\n" .
            'Типичный стек:' . "\n" .
            '- php:8.2-fpm-alpine — лёгкий образ с PHP-FPM' . "\n" .
            '- nginx:alpine — веб-сервер' . "\n" .
            '- mysql:8.0 — база данных' . "\n\n" .
            'Ключевые моменты:' . "\n" .
            '1. volumes для bind-mount кода' . "\n" .
            '2. depends_on с condition: service_healthy' . "\n" .
            '3. Отдельные сети для изоляции проектов',
        'views' => 1876, 'image' => 'docker-php.svg', 'daysAgo' => 12,
        'categories' => ['DevOps', 'PHP'],
    ],
    [
        'title' => 'XSS-защита в шаблонах: escape по умолчанию',
        'description' => 'Как настроить автоэскейпинг в Smarty и других шаблонизаторах, чтобы забыть про XSS.',
        'content' => 'XSS (Cross-Site Scripting) — уязвимость, при которой злоумышленник внедряет JavaScript в страницу.' . "\n\n" .
            'Защита: экранировать все пользовательские данные перед выводом. В Smarty 5 это делается одной настройкой:' . "\n\n" .
            'default_modifiers = [\'escape:"html"\'];' . "\n\n" .
            'Теперь любая переменная автоматически пропускается через htmlspecialchars. Для доверенного HTML используется флаг nofilter.',
        'views' => 734, 'image' => null, 'daysAgo' => 15,
        'categories' => ['Безопасность'],
    ],
    [
        'title' => 'Принципы SOLID: реальные примеры на PHP',
        'description' => 'Разбираем каждый принцип SOLID с практическими примерами, а не абстрактными определениями.',
        'content' => 'SOLID — пять принципов, которые делают код гибким и поддерживаемым.' . "\n\n" .
            'SRP: класс делает одну вещь. UserRepo не должен логировать.' . "\n" .
            'OCP: расширение без модификации. Интерфейсы + полиморфизм.' . "\n" .
            'LSP: наследник заменяет родителя. Не ломаем контракт.' . "\n" .
            'ISP: много мелких интерфейсов лучше одного большого.' . "\n" .
            'DIP: зависимости от абстракций, а не от реализаций.' . "\n\n" .
            'Главное — не применять догматично. SOLID — инструмент, а не религия.',
        'views' => 3421, 'image' => 'solid-php.svg', 'daysAgo' => 20,
        'categories' => ['Архитектура', 'PHP'],
    ],
    [
        'title' => 'Enum в PHP 8.1: зачем и как использовать',
        'description' => 'Бэкед-enum как типобезопасная альтернатива строковым константам.',
        'content' => 'До PHP 8.1 для "наборов значений" использовали константы класса. Enum делает это типобезопасно.' . "\n\n" .
            'Особенно полезен backed enum — когда каждому кейсу соответствует значение:' . "\n\n" .
            'enum SortOrder: string {' . "\n" .
            '    case DateDesc = \'date_desc\';' . "\n" .
            '    case DateAsc = \'date_asc\';' . "\n" .
            '}' . "\n\n" .
            'Главная выгода: SortOrder::tryFrom(\$input) вместо кучи if\'ов и проверки whitelist. Если значение невалидно — получаем null, а не баг.',
        'views' => 1156, 'image' => null, 'daysAgo' => 25,
        'categories' => ['PHP'],
    ],
    [
        'title' => 'Индексы в MySQL: когда они ускоряют, а когда замедляют',
        'description' => 'Практическое руководство по индексам: B-Tree, составные индексы, покрывающие индексы.',
        'content' => 'Индексы — как оглавление в книге. Ускоряют поиск, но замедляют запись.' . "\n\n" .
            'Когда индекс помогает:' . "\n" .
            '- WHERE по индексированной колонке' . "\n" .
            '- ORDER BY по индексированной колонке' . "\n" .
            '- JOIN по индексированной колонке' . "\n\n" .
            'Когда не помогает:' . "\n" .
            '- SELECT * (не покрывающий)' . "\n" .
            '- Функции от колонки в WHERE' . "\n" .
            '- LIKE \'%term%\'' . "\n\n" .
            'Золотое правило: индексируйте то, по чему реально фильтруете и сортируете. Лишние индексы замедляют INSERT/UPDATE.',
        'views' => 2847, 'image' => 'mysql-indexes.svg', 'daysAgo' => 30,
        'categories' => ['Базы данных'],
    ],
    [
        'title' => 'Иммутабельные объекты в PHP',
        'description' => 'Почему readonly-свойства и withX()-методы делают код предсказуемее.',
        'content' => 'Иммутабельный объект — объект, который не меняется после создания. Любое "изменение" возвращает новую копию.' . "\n\n" .
            'Преимущества:' . "\n" .
            '- Нет скрытых мутаций' . "\n" .
            '- Легко рассуждать о коде' . "\n" .
            '- Безопасно в многопоточке' . "\n" .
            '- Предсказуемо в тестах' . "\n\n" .
            'В PHP 8.1+ это делается через readonly-свойства и метод withCategories(), который возвращает новую копию с добавленными категориями.',
        'views' => 965, 'image' => null, 'daysAgo' => 35,
        'categories' => ['PHP', 'Архитектура'],
    ],
    [
        'title' => 'CI/CD для PHP-проекта с GitHub Actions',
        'description' => 'Автоматизируем тесты, линтеры и деплой через GitHub Actions.',
        'content' => 'GitHub Actions — бесплатный CI/CD для open-source проектов.' . "\n\n" .
            'Типичный пайплайн для PHP:' . "\n" .
            '1. Checkout кода' . "\n" .
            '2. Настройка PHP с нужными расширениями' . "\n" .
            '3. composer install' . "\n" .
            '4. PHPStan для статического анализа' . "\n" .
            '5. PHPUnit для тестов' . "\n" .
            '6. PHP-CS-Fixer для стиля' . "\n\n" .
            'Всё описывается в .github/workflows/ci.yml. При каждом PR проверки запускаются автоматически.',
        'views' => 1234, 'image' => 'github-actions.svg', 'daysAgo' => 40,
        'categories' => ['DevOps'],
    ],
    [
        'title' => 'Async/Await в JavaScript: подводные камни',
        'description' => 'Что нужно знать, чтобы не наделать ошибок с асинхронным кодом.',
        'content' => 'Async/await делает асинхронный код похожим на синхронный. Но за простотой скрываются ловушки.' . "\n\n" .
            'Типичные ошибки:' . "\n" .
            '1. Забытый await — промис не разрешается' . "\n" .
            '2. Цикл с await внутри — последовательное выполнение вместо параллельного' . "\n" .
            '3. Отсутствие try/catch — необработанные rejection' . "\n" .
            '4. Смешивание .then() и await — плохая читаемость' . "\n\n" .
            'Правильный паттерн для параллельных запросов: Promise.all([fetch1(), fetch2()]).',
        'views' => 1567, 'image' => null, 'daysAgo' => 45,
        'categories' => ['JavaScript'],
    ],
    [
        'title' => 'Миграции БД: почему свой Migrator лучше готовых библиотек',
        'description' => 'Пишем систему миграций на 150 строк кода вместо установки Phinx или doctrine/migrations.',
        'content' => 'Phinx, doctrine/migrations, Laravel Migrations — все они делают одно и то же. Но для небольшого проекта своя реализация часто лучше.' . "\n\n" .
            'Почему:' . "\n" .
            '- 150 строк против тысяч в библиотеке' . "\n" .
            '- Полный контроль над поведением' . "\n" .
            '- Никаких лишних зависимостей' . "\n" .
            '- Понимание, как это работает изнутри' . "\n\n" .
            'Минимум: таблица migrations для трекинга, класс Migration с up()/down(), класс Migrator для применения.',
        'views' => 687, 'image' => null, 'daysAgo' => 50,
        'categories' => ['Базы данных', 'PHP'],
    ],
    [
        'title' => 'CSRF-защита: теория и реализация',
        'description' => 'Что такое CSRF-атака и как защититься от неё с помощью токенов.',
        'content' => 'CSRF (Cross-Site Request Forgery) — атака, при которой злоумышленник заставляет браузер жертвы выполнить нежелательное действие на другом сайте.' . "\n\n" .
            'Пример: пользователь залогинен в банке, открывает вредоносную страницу, которая отправляет POST-запрос на перевод денег.' . "\n\n" .
            'Защита: CSRF-токен.' . "\n" .
            '1. Генерируем случайный токен и кладём в сессию' . "\n" .
            '2. Вставляем в форму как скрытое поле' . "\n" .
            '3. При POST сравниваем токен из формы с токеном из сессии' . "\n" .
            '4. Используем hash_equals для сравнения (защита от timing attacks)',
        'views' => 1893, 'image' => 'csrf.svg', 'daysAgo' => 55,
        'categories' => ['Безопасность'],
    ],
    [
        'title' => 'N+1 проблема и как её решать',
        'description' => 'Почему цикл с SQL-запросами убивает производительность и какие есть решения.',
        'content' => 'N+1 — классическая проблема ORM и наивного кода. Для N записей выполняется N+1 запрос к БД.' . "\n\n" .
            'Пример:' . "\n" .
            '- 1 запрос: получить 100 статей' . "\n" .
            '- 100 запросов: для каждой получить автора' . "\n\n" .
            'Решения:' . "\n" .
            '1. JOIN в одном запросе (но дубликаты данных)' . "\n" .
            '2. Eager loading: 2 запроса (статьи + авторы по списку ID)' . "\n" .
            '3. Оконные функции для топ-N в группах' . "\n" .
            '4. Кэширование на уровне приложения' . "\n\n" .
            'Оконные функции — самый элегантный способ для задачи "последние N статей в каждой категории".',
        'views' => 2234, 'image' => 'n-plus-one.svg', 'daysAgo' => 60,
        'categories' => ['Базы данных', 'Архитектура'],
    ],
    [
        'title' => 'Type hints и strict_types в PHP 8',
        'description' => 'Как строгая типизация помогает ловить баги на этапе разработки.',
        'content' => 'declare(strict_types=1) в начале файла — это сигнал PHP не делать неявных преобразований типов.' . "\n\n" .
            'Без strict_types:' . "\n" .
            'foo(\'123\') принимает \'123\' там, где ожидается int' . "\n\n" .
            'С strict_types:' . "\n" .
            'foo(\'123\') бросает TypeError, если сигнатура foo(int \$x)' . "\n\n" .
            'Это ловит баги на этапе разработки, а не в продакшене. Особенно полезно в больших командах, где типы в сигнатурах — часть контракта.',
        'views' => 756, 'image' => null, 'daysAgo' => 65,
        'categories' => ['PHP'],
    ],
    [
        'title' => 'React vs Vue vs Svelte в 2026 году',
        'description' => 'Сравнение трёх популярных фреймворков для фронтенда.',
        'content' => 'Выбор фреймворка зависит от проекта и команды.' . "\n\n" .
            'React:' . "\n" .
            '- Огромная экосистема' . "\n" .
            '- JSX — мощно, но непривычно' . "\n" .
            '- Next.js для SSR' . "\n\n" .
            'Vue:' . "\n" .
            '- Низкий порог входа' . "\n" .
            '- Отличная документация' . "\n" .
            '- Nuxt для SSR' . "\n\n" .
            'Svelte:' . "\n" .
            '- Компиляция в ванильный JS' . "\n" .
            '- Минимальный рантайм' . "\n" .
            '- SvelteKit для full-stack' . "\n\n" .
            'Для нового проекта в 2026: React для больших команд, Vue для малых, Svelte для производительности.',
        'views' => 3891, 'image' => 'frontend.svg', 'daysAgo' => 70,
        'categories' => ['JavaScript'],
    ],
    [
        'title' => 'Docker volumes: bind-mount vs named volumes',
        'description' => 'Когда использовать bind-mount, а когда named volume — и почему это важно.',
        'content' => 'Docker поддерживает два типа томов:' . "\n\n" .
            'Bind-mount (./src:/app/src):' . "\n" .
            '- Папка хоста видна в контейнере' . "\n" .
            '- Изменения с хоста сразу в контейнере' . "\n" .
            '- Идеально для кода при разработке' . "\n\n" .
            'Named volume (mysql_data):' . "\n" .
            '- Docker сам управляет хранением' . "\n" .
            '- Персистентно между пересозданиями контейнера' . "\n" .
            '- Идеально для БД и пользовательских данных' . "\n\n" .
            'Правило: код — bind-mount, данные — named volume.',
        'views' => 1423, 'image' => null, 'daysAgo' => 75,
        'categories' => ['DevOps'],
    ],
    [
        'title' => 'Repository pattern: когда он нужен, а когда нет',
        'description' => 'Разбираем, зачем репозитории и в каких проектах они избыточны.',
        'content' => 'Repository — паттерн, который абстрагирует работу с данными за интерфейсом.' . "\n\n" .
            'Когда нужен:' . "\n" .
            '- Много источников данных (БД + API + кэш)' . "\n" .
            '- Сложная бизнес-логика запросов' . "\n" .
            '- Юнит-тесты с моками БД' . "\n" .
            '- Команда с разным уровнем опыта' . "\n\n" .
            'Когда избыточен:' . "\n" .
            '- Простое CRUD-приложение' . "\n" .
            '- Один разработчик' . "\n" .
            '- Прототип или MVP' . "\n\n" .
            'Репозиторий — не серебряная пуля. Он добавляет абстракцию, а значит и сложность. Используйте осознанно.',
        'views' => 1678, 'image' => null, 'daysAgo' => 80,
        'categories' => ['Архитектура', 'PHP'],
    ],
];

$clearOnly = in_array('--clear', $argv, true);

$inTransaction = false;
try {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec('TRUNCATE TABLE article_category');
    $pdo->exec('TRUNCATE TABLE articles');
    $pdo->exec('TRUNCATE TABLE categories');
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    if ($clearOnly) {
        echo "Таблицы очищены.\n";
        exit(0);
    }

    $pdo->beginTransaction();
    $inTransaction = true;

    // Категории
    $categoryIds = [];
    $catStmt = $pdo->prepare('INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)');
    foreach ($categories as $cat) {
        $catStmt->execute([$cat['name'], slugify($cat['name']), $cat['description']]);
        $categoryIds[$cat['name']] = (int) $pdo->lastInsertId();
    }

    // Статьи + генерация SVG-обложек
    $articleIds = [];
    $artStmt = $pdo->prepare(
        'INSERT INTO articles (title, slug, description, content, image, views, published_at)
         VALUES (?, ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))'
    );
    foreach ($articles as $i => $art) {
        if ($art['image'] !== null) {
            $svg = makePlaceholderSvg($art['title'], $palette[$i % count($palette)]);
            @file_put_contents($uploadDir . '/' . $art['image'], $svg);
        }
        $artStmt->execute([
            $art['title'],
            slugify($art['title']),
            $art['description'],
            $art['content'],
            $art['image'],
            $art['views'],
            $art['daysAgo'],
        ]);
        $articleIds[$art['title']] = (int) $pdo->lastInsertId();
    }

    // Связи many-to-many
    $linkStmt = $pdo->prepare('INSERT INTO article_category (article_id, category_id) VALUES (?, ?)');
    $linksCount = 0;
    foreach ($articles as $art) {
        foreach ($art['categories'] as $catName) {
            $linkStmt->execute([$articleIds[$art['title']], $categoryIds[$catName]]);
            $linksCount++;
        }
    }

    $pdo->commit();
    $inTransaction = false;

    echo "Сидинг завершён успешно.\n";
    echo "Категорий: " . count($categories) . "\n";
    echo "Статей: " . count($articles) . "\n";
    echo "Связей статья-категория: {$linksCount}\n";
} catch (Throwable $e) {
    if ($inTransaction && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Ошибка: " . $e->getMessage() . "\n");
    exit(1);
}
