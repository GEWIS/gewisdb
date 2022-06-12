<?php

return [
    'controllers' => [
        'invokables' => [
            'Report\Controller\Report' => 'Report\Controller\ReportController'
        ]
    ],
    'doctrine' => [
        'configuration' => [
            'orm_report' => [
                'metadata_cache' => 'array',
                'query_cache' => 'array',
                'result_cache' => 'array',
                'hydration_cache' => 'array',
                'driver' => 'orm_report',
                'generate_proxies' => true,
                'proxy_dir' => 'data/DoctrineORMModule/Proxy',
                'proxy_namespace' => 'DoctrineORMModule\Proxy',
                'filters' => [],
                'entity_namespaces' => [
                    'db' => 'Report\Model'
                ]
            ]
        ],
        'driver' => [
            'Report_Driver' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => [
                    __DIR__ . '/../src/Report/Model'
                ]
            ],
            'orm_report' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\DriverChain',
                'drivers' => [
                    'Report\Model' => 'Report_Driver'
                ]
            ]
        ],
        'entitymanager' => [
            'orm_report' => [
                'connection' => 'orm_report',
                'configuration' => 'orm_report'
            ]
        ],
        'eventmanager' => [
            'orm_report' => []
        ],
        'sql_logger_collector' => [
            'orm_report' => []
        ],
        'entity_resolver' => [
            'orm_report' => []
        ]
    ],
    'console' => [
        'router' => [
            'routes' => [
                'generate_reportdb' => [
                    'options' => [
                        'route' => 'generate reportdb',
                        'defaults' => [
                            'controller' => 'Report\Controller\Report',
                            'action' => 'generate'
                        ]
                    ]
                ],
                'generate_reportdb_full' => [
                    'options' => [
                        'route' => 'generate reportdb full',
                        'defaults' => [
                            'controller' => 'Report\Controller\Report',
                            'action' => 'generateAll'
                        ]
                    ]
                ]
            ]
        ]
    ],
    'mail' => [
    ]
];
