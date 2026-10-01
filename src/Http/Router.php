<?php

declare(strict_types=1);

namespace App\Http;

use App\Exception\NotFoundException;
use App\Support\Container;

/**
 * Простой regex-роутер с типобезопасными обработчиками.
 *
 * Обработчик задаётся массивом [Controller::class, 'method'] - это
 * позволяет IDE и PHPStan ловить опечатки в именах классов и методов.
 * Параметры из URL ({slug}, {id}) передаются в экшен контроллера
 * дополнительными аргументами после Request.
 */
final class Router
{
    /**
     * Зарегистрированные маршруты.
     *
     * @var array<int, array{
     *     method: string,
     *     pattern: string,
     *     handler: array{0: class-string, 1: string}
     * }>
     */
    private array $routes = [];

    public function __construct(private readonly Container $container)
    {
    }

    /**
     * Регистрирует GET-маршрут.
     *
     * @param string $pattern Шаблон, например /category/{slug}
     * @param array{0: class-string, 1: string} $handler [Controller::class, 'method']
     */
    public function get(string $pattern, array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    /**
     * Регистрирует POST-маршрут.
     *
     * @param array{0: class-string, 1: string} $handler
     */
    public function post(string $pattern, array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    /**
     * Добавляет маршрут во внутренний список.
     *
     * @param array{0: class-string, 1: string} $handler
     */
    private function add(string $method, string $pattern, array $handler): void
    {
        $this->routes[] = [
            'method'  => $method,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    /**
     * Находит и вызывает обработчик для запроса.
     *
     * @throws NotFoundException Если подходящий маршрут не найден
     */
    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }

            $regex = $this->patternToRegex($route['pattern']);
            if (preg_match($regex, $request->path, $matches) !== 1) {
                continue;
            }

            // Из $matches берём только именованные группы - это параметры URL.
            $params = array_filter(
                $matches,
                static fn (string|int $key): bool => is_string($key),
                ARRAY_FILTER_USE_KEY,
            );

            [$controllerClass, $method] = $route['handler'];

            /** @var object $controller */
            $controller = $this->container->get($controllerClass);

            /** @var Response $response */
            $response = $controller->{$method}($request, ...array_values($params));

            return $response;
        }

        throw new NotFoundException("Маршрут не найден: {$request->method} {$request->path}");
    }

    /**
     * Преобразует шаблон /category/{slug} в регулярное выражение.
     *
     * preg_quote экранирует всё, включая точки и слеши, потом фигурные
     * скобки разэкранируются в именованные группы. Безопасно для любых
     * шаблонов - от инъекций регекспа защищает preg_quote.
     */
    private function patternToRegex(string $pattern): string
    {
        $escaped = preg_quote($pattern, '#');

        // preg_quote превращает { в \{, а } в \}. Разэкранируем их
        // обратно, чтобы плейсхолдеры остались валидными regex-группами.
        $regex = preg_replace(
            '#\\\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\\\}#',
            '(?P<$1>[^/]+)',
            $escaped,
        );

        return '#^' . $regex . '$#';
    }
}
