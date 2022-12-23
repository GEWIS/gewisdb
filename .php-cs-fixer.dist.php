<?php

$finder = PhpCsFixer\Finder::create()
    ->path(
        [
            'module/',
            'config/',
        ]
    )
    ->name('*.php')
    ->in(__DIR__);

return (new PhpCsFixer\Config())
    ->setCacheFile('data/cache/.php-cs-fixer.cache')
    ->setRules(
        [
            '@PSR1' => true,
            '@PSR12' => true,
            '@PHP82Migration' => true,
        ]
    )
    ->setFinder($finder);
