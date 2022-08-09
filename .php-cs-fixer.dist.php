<?php

$finder = PhpCsFixer\Finder::create()
    ->path(['module/', 'config/'])
    ->name('*.php')
    ->in(__DIR__)
;

$config = new PhpCsFixer\Config();
$config->setCacheFile('data/cache/.php-cs-fixer.cache');
return $config->setRules([
    '@PSR1' => true,
    '@PSR12' => true,
    '@DoctrineAnnotation' => true,
    '@PHP81Migration' => true,
    ])
    ->setFinder($finder)
;
