<?php

namespace Report;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Report\Command\{
    GenerateFullCommand,
    GeneratePartialCommand,
};

return array(
    'doctrine' => array(
        'configuration' => array(
            'orm_report' => array(
                'entity_namespaces' => array(
                    'db' => __NAMESPACE__ . '\Model',
                ),
            ),
        ),
        'driver' => array(
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
        ),
    ),
    'laminas-cli' => array(
        'commands' => array(
            'report:generate:partial' => GeneratePartialCommand::class,
            'report:generate:full' => GenerateFullCommand::class,
        ),
    ),
    'mail' => []
);
