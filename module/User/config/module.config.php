<?php
use User\Controller\UserController;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use User\Mapper\UserMapper;
use User\Mapper\Factory\UserMapperFactory;

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
            ]
        ]
    ],
    'service_manager' => [
        'factories' => [
            UserMapper::class => UserMapperFactory::class,
        ]
    ],
    'controllers' => [
        'invokables' => [
            UserController::class => UserController::class,
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
