<?php

use User\Controller\UserController;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Laminas\Authentication\AuthenticationService;
use Laminas\Crypt\Password\PasswordInterface;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use User\Mapper\UserMapper;
use User\Mapper\Factory\UserMapperFactory;
use User\Service\UserService;
use User\Service\Factory\UserServiceFactory;
use User\Controller\Factory\UserControllerFactory;
use User\Controller\SettingsController;
use User\Controller\Factory\SettingsControllerFactory;
use User\Form\UserCreate;
use User\Form\Login;
use User\Factory\PasswordFactory;
use User\Model\User;
use User\Service\Factory\AuthenticationServiceFactory;
use User\Form\UserEdit;

return [
    'router' => [
        'routes' => [
            'user' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/user',
                    'defaults' => [
                        'controller' => UserController::class,
                        'action' => 'index'
                    ]
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'default' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:action'
                        ]
                    ]
                ]
            ],
            // settings route is already defined in the database module
            'settings' => [
                'child_routes' => [
                    'user' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/user',
                            'defaults' => [
                                'controller' => SettingsController::class,
                                'action' => 'index'
                            ]
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'default' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/:action',
                                ]
                            ],
                            'edit' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/edit/:id',
                                    'defaults' => [
                                        'action' => 'edit'
                                    ]
                                ]
                            ],
                            'delete' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/delete/:id',
                                    'defaults' => [
                                        'action' => 'remove'
                                    ]
                                ]
                            ],
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
            PasswordInterface::class => PasswordFactory::class,
            AuthenticationService::class => AuthenticationServiceFactory::class,
        ],
        'invokables' => [
            UserCreate::class => UserCreate::class,
            UserEdit::class => UserEdit::class,
            Login::class => Login::class,
        ],
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
        ],
        'authentication' => [
            'orm_default' => [
                'object_manager' => 'database_doctrine_em',
                'identity_class' => User::class,
                'identity_property' => 'login',
                'credential_property' => 'password'
            ]
        ]
    ]
];
