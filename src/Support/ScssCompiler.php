<?php

declare(strict_types=1);

namespace App\Support;

use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;

/**
 * Ленивая компиляция SCSS в CSS.
 *
 * Компилирует только если исходник новее результата (проверка по mtime),
 * чтобы не гонять компилятор на каждом запросе.
 */
final class ScssCompiler
{
    public function __construct(
        private readonly string $sourcePath,
        private readonly string $targetPath,
    ) {
    }

    /**
     * Компилирует SCSS, если CSS отсутствует или устарел.
     *
     * @return bool true, если компиляция выполнялась
     */
    public function compileIfStale(): bool
    {
        if (is_file($this->targetPath)
            && filemtime($this->targetPath) >= filemtime($this->sourcePath)
        ) {
            return false;
        }

        $compiler = new Compiler();
        $compiler->setOutputStyle(OutputStyle::COMPRESSED);
        $compiler->setImportPaths(dirname($this->sourcePath));

        $css = $compiler->compileString(file_get_contents($this->sourcePath))->getCss();

        $dir = dirname($this->targetPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($this->targetPath, $css);

        return true;
    }
}
