<?php

declare(strict_types=1);

/**
 * CLI-компилятор SCSS в CSS.
 *
 * Использование: php bin/scss.php
 */

use App\Support\ScssCompiler;

require __DIR__ . '/../vendor/autoload.php';

$root = dirname(__DIR__);

$compiler = new ScssCompiler(
    $root . '/resources/scss/app.scss',
    $root . '/public/css/app.css',
);

echo $compiler->compileIfStale()
    ? "CSS skompilirovan.\n"
    : "CSS aktualen, kompilyaciya ne nuzhna.\n";
