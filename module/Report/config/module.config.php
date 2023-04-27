<?php

declare(strict_types=1);

namespace Report;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Report\Command\GenerateFullCommand;
use Report\Command\GeneratePartialCommand;

return [
    'doctrine' => [
        'configuration' => [
            'orm_report' => [
                'entity_namespaces' => [
                    'db' => __NAMESPACE__ . '\Model',
                ],
            ],
        ],
        'driver' => [
            __NAMESPACE__ . '_driver' => [
                'class' => AttributeDriver::class,
                'paths' => [
                    __DIR__ . '/../src/Model/',
                ],
            ],
            'orm_report' => [
                'drivers' => [
                    __NAMESPACE__ . '\Model' => __NAMESPACE__ . '_driver',
                ],
            ],
        ],
    ],
    'laminas-cli' => [
        'commands' => [
            'report:generate:partial' => GeneratePartialCommand::class,
            'report:generate:full' => GenerateFullCommand::class,
        ],
    ],
    'mail' => [],
];
