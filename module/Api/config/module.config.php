<?php
use Api\Controller\AdminController;
use Api\Controller\Factory\AdminControllerFactory;
use Api\Service\ApiKey as ApiKeyService;
use Api\Mapper\ApiKey as ApiKeyMapper;

return [
    'router' => [
        'routes' => [
            'api' => [
                'type' => 'Literal',
                'options' => [
                    'route'    => '/api',
                    'defaults' => [
                        '__NAMESPACE__' => 'Api\Controller',
                        'controller' => 'Index',
                        'action'     => 'index',
                    ],
                ],
            ],
            // settings route is already defined in the database module
            'settings' => [
                'child_routes' => [
                    'api' => [
                        'type' => 'literal',
                        'options' => [
                            'route' => '/api',
                            'defaults' => [
                                '__NAMESPACE__' => '',
                                'controller' => \Api\Controller\AdminController::class,
                                'action' => 'index'
                            ]
                        ]
                    ]
                ]
            ]
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Api\Controller\Index' => \Api\Controller\IndexController::class,
        ],
        'factories' => [
            AdminController::class => AdminControllerFactory::class,
        ]
    ],
    'view_manager' => [
        'template_path_stack' => [
            'api' => __DIR__ . '/../view/'
        ],
        'strategies' => [
            'ViewJsonStrategy'
        ]
    ],
    'service_manager' => [
        'factories' => [
            ApiKeyService::class => \Api\Service\Factory\ApiKeyFactory::class,
            ApiKeyMapper::class => \Api\Mapper\Factory\ApiKeyFactory::class,
        ]
    ],
    'doctrine' => [
        'driver' => [
            'api_entities' => [
                'class' => \Doctrine\ORM\Mapping\Driver\AnnotationDriver::class,
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Model']
            ],
            'orm_default' => [
                'drivers' => [
                    'Api\Model' => 'api_entities'
                ]
            ]
        ],
    ]
];
