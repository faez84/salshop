<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Php74\Rector\LNumber\AddLiteralSeparatorToNumberRector;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
    ]);
    $rectorConfig->skip(
        [
            AddLiteralSeparatorToNumberRector::class,
            __DIR__ . '/src/Kernel.php',
        ]
    );
    $rectorConfig->import(LevelSetList::UP_TO_PHP_80);
    $rectorConfig->parallel(600, 16, 20);
    $rectorConfig->phpstanConfig(__DIR__ . '/phpstan.neon');
    // register a single rule
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);
    // define sets of rules
    //    $rectorConfig->sets([
    //        LevelSetList::UP_TO_PHP_82
    //    ]);
};
