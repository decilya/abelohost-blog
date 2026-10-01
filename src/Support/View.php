<?php

declare(strict_types=1);

namespace App\Support;

use Smarty\Smarty;

/**
 * Обёртка над Smarty.
 *
 * Ключевая настройка безопасности: default_modifiers = ['escape:"html"'].
 * Все переменные в шаблонах экранируются автоматически - XSS-защита
 * работает по умолчанию, |escape в шаблонах писать не нужно.
 * Для доверенного HTML используется атрибут nofilter.
 */
final class View
{
    private Smarty $smarty;

    /**
     * @param array<string, mixed> $config Конфигурация из config/app.php
     */
    public function __construct(array $config)
    {
        // Smarty падает при отсутствии папок кэша - создаём заранее.
        foreach (['smarty_cache', 'smarty_compile'] as $key) {
            if (!is_dir($config[$key])) {
                mkdir($config[$key], 0777, true);
            }
        }

        $this->smarty = new Smarty();
        $this->smarty->setTemplateDir($config['views_path']);
        $this->smarty->setCompileDir($config['smarty_compile']);
        $this->smarty->setCacheDir($config['smarty_cache']);
        $this->smarty->setCaching(Smarty::CACHING_OFF);
        // В debug перекомпилируем шаблоны при каждом изменении.
        $this->smarty->setForceCompile($config['debug']);

        // Автоэскейпинг всех переменных (XSS-защита по умолчанию).
        $this->smarty->default_modifiers = ['escape:"html"'];

        // Глобальные переменные, доступны во всех шаблонах.
        $this->smarty->assign('app_name', $config['name']);
        $this->smarty->assign('app_url', $config['url']);
    }

    /**
     * Рендерит шаблон и возвращает готовый HTML.
     *
     * @param string $template Имя файла шаблона относительно views_path
     * @param array<string, mixed> $data Переменные для шаблона
     */
    public function render(string $template, array $data = []): string
    {
        foreach ($data as $key => $value) {
            $this->smarty->assign($key, $value);
        }

        return $this->smarty->fetch($template);
    }
}
