<?php

declare(strict_types=1);

namespace App\Support;

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use RuntimeException;

/**
 * Простой DI-контейнер с автовайрингом через рефлексию.
 *
 * Умеет:
 *  - возвращать синглтоны, зарегистрированные через singleton();
 *  - создавать новый экземпляр через bind() при каждом get();
 *  - разрешать произвольный класс через рефлексию его конструктора;
 *  - разрешать зависимости по type-hints рекурсивно.
 *
 * Не кэширует автоматически созданные объекты - каждый get() на незарегистрированный
 * класс создаёт новый экземпляр. Это предсказуемо: если нужен синглтон,
 * регистрируй его явно через singleton() или instance().
 */
final class Container
{
    /** @var array<string, callable(self): object> Фабрики, зарегистрированные через bind/singleton */
    private array $bindings = [];

    /** @var array<string, object> Уже созданные синглтоны и готовые экземпляры */
    private array $instances = [];

    /**
     * Регистрирует фабрику. Новый экземпляр создаётся при каждом get().
     *
     * @param string $abstract Обычно FQCN класса или интерфейса
     * @param callable(self): object $factory
     */
    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    /**
     * Регистрирует синглтон: объект создаётся один раз и переиспользуется.
     *
     * @param callable(self): object $factory
     */
    public function singleton(string $abstract, callable $factory): void
    {
        $this->bind($abstract, function (self $container) use ($abstract, $factory): object {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $factory($container);
            }

            return $this->instances[$abstract];
        });
    }

    /**
     * Регистрирует готовый экземпляр.
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Возвращает объект по его классу или интерфейсу.
     *
     * Порядок разрешения:
     *  1. Готовые инстансы (instance/singleton).
     *  2. Binding-фабрики.
     *  3. Reflection-резолвинг класса.
     *
     * @template T of object
     * @param class-string<T> $abstract
     * @return T
     * @throws RuntimeException Если класс не найден и не зарегистрирован
     */
    public function get(string $abstract): object
    {
        if (isset($this->instances[$abstract])) {
            /** @var T */
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            /** @var T */
            return $this->bindings[$abstract]($this);
        }

        /** @var T */
        return $this->resolve($abstract);
    }

    /**
     * Создаёт объект через рефлексию, разрешая зависимости конструктора.
     *
     * Для каждого параметра конструктора:
     *  - если тип - класс, рекурсивно разрешаем через get();
     *  - если тип скалярный и есть значение по умолчанию - используем его;
     *  - иначе - исключение с понятным сообщением.
     *
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    private function resolve(string $class): object
    {
        if (!class_exists($class)) {
            throw new RuntimeException("Класс не найден в контейнере: {$class}");
        }

        try {
            $reflection = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new RuntimeException(
                "Ошибка рефлексии для {$class}: {$e->getMessage()}",
                0,
                $e,
            );
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $args = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            // Параметр без типа: надеемся на значение по умолчанию.
            if (!$type instanceof ReflectionNamedType) {
                if ($parameter->isDefaultValueAvailable()) {
                    $args[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new RuntimeException(sprintf(
                    'Параметр $%s класса %s не имеет типа и значения по умолчанию',
                    $parameter->getName(),
                    $class,
                ));
            }

            // Скалярный (int, string, bool, ...): используем значение по умолчанию.
            if ($type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $args[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new RuntimeException(sprintf(
                    'Скалярный параметр $%s класса %s не имеет значения по умолчанию',
                    $parameter->getName(),
                    $class,
                ));
            }

            // Класс или интерфейс: рекурсивно разрешаем.
            $args[] = $this->get($type->getName());
        }

        return $reflection->newInstanceArgs($args);
    }
}
