<?php

declare(strict_types=1);

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use IgorPhp\IgorBundle\IgorPhpBundle;
use Nelmio\CorsBundle\NelmioCorsBundle;
use Nelmio\SecurityBundle\NelmioSecurityBundle;
use Sensiolabs\TypeScriptBundle\SensiolabsTypeScriptBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\UX\LiveComponent\LiveComponentBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use Symfonycasts\SassBundle\SymfonycastsSassBundle;
use Twig\Extra\TwigExtraBundle\TwigExtraBundle;

return [
    FrameworkBundle::class => ['all' => true],
    TwigBundle::class => ['all' => true],
    TwigExtraBundle::class => ['all' => true],
    StimulusBundle::class => ['all' => true],
    TwigComponentBundle::class => ['all' => true],
    LiveComponentBundle::class => ['all' => true],
    ApiPlatformBundle::class => ['all' => true],
    DoctrineBundle::class => ['all' => true],
    DoctrineFixturesBundle::class => [
        'dev' => true,
        'test' => true,
    ],
    DoctrineMigrationsBundle::class => ['all' => true],
    DAMADoctrineTestBundle::class => ['test' => true],
    NelmioCorsBundle::class => ['all' => true],
    SensiolabsTypeScriptBundle::class => ['all' => true],
    DebugBundle::class => ['dev' => true],
    MakerBundle::class => ['dev' => true],
    MonologBundle::class => ['all' => true],
    SecurityBundle::class => ['all' => true],
    WebProfilerBundle::class => [
        'dev' => true,
        'test' => true,
    ],
    SymfonycastsSassBundle::class => ['all' => true],
    IgorPhpBundle::class => [
        'dev' => true,
        'test' => true,
    ],
    NelmioSecurityBundle::class => ['all' => true],
];
