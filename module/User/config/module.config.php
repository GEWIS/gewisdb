<?php
use User\Controller\UserController;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use User\Mapper\UserMapper;
use User\Mapper\Factory\UserMapperFactory;
use User\Service\UserService;
use User\Service\Factory\UserServiceFactory;
use User\Controller\Factory\UserControllerFactory;
use User\Controller\SettingsController;
use User\Controller\Factory\SettingsControllerFactory;

return [
    'router' => [
        'routes' => [
            'user' => [
                'type' => 'literal',
                'options' => [
                    'route' => '/user',
                    'defaults' => [
                        'controller' => UserController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            // settings route is already defined in the database module
            'settings' => [
                'child_routes' => [
                    'user' => [
                        'type' => 'literal',
                        'options' => [
                            'route' => '/user',
                            'defaults' => [
                                '__NAMESPACE__' => '',
                                'controller' => SettingsController::class,
                                'action' => 'index'
                            ]
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'default' => [
                                'type' => 'segment',
                                'options' => [
                                    'route' => '/:action',
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ],
    'service_manager' => [
        'factories' => [
            UserService::class => UserServiceFactory::class,
            UserMapper::class => UserMapperFactory::class,
        ]
    ],
    'controllers' => [
        'factories' => [
            UserController::class => UserControllerFactory::class,
            SettingsController::class => SettingsControllerFactory::class,
        ]
    ],
    'view_manager' => [
        'template_path_stack' => [
            'user' => __DIR__ . '/../view/'
        ]
    ],
    'doctrine' => [
        'driver' => [
            'user_entities' => [
                'class' => AnnotationDriver::class,
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Model/']
            ],
            'orm_default' => [
                'drivers' => [
                    'User\Model' => 'user_entities'
                ]
            ]
        ]
    ]
];
