<?php

namespace User;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Laminas\Authentication\AuthenticationService;
use Laminas\Crypt\Password\PasswordInterface;
use Laminas\Router\Http\{
    Literal,
    Segment,
};
use User\Adapter\ApiPrincipalAdapter;
use User\Adapter\Factory\ApiPrincipalAdapterFactory;
use User\Controller\{
    UserController,
    SettingsController,
};
use User\Controller\Factory\{
    SettingsControllerFactory,
    UserControllerFactory,
};
use User\Factory\PasswordFactory;
use User\Listener\AuthenticationListener;
use User\Mapper\{
    ApiPrincipalMapper,
    UserMapper,
};
use User\Mapper\Factory\{
    ApiPrincipalMapperFactory,
    UserMapperFactory,
};
use User\Model\User;
use User\Form\{
    Login,
    UserCreate,
    UserEdit,
};
use User\Service\{
    ApiAuthenticationService,
    UserService,
};
use User\Service\Factory\{
    ApiAuthenticationServiceFactory,
    AuthenticationServiceFactory,
    UserServiceFactory,
};

return [
    'router' => [
        'routes' => [
            'user' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/user',
                    'defaults' => [
                        'controller' => UserController::class,
                        'action' => 'index',
                        'auth_type' => AuthenticationListener::AUTH_NONE,
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'default' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:action',
                        ],
                    ],
                ],
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
                                'action' => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'default' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/:action',
                                ],
                            ],
                            'edit' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/edit/:id',
                                    'defaults' => [
                                        'action' => 'edit',
                                    ],
                                ],
                            ],
                            'delete' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/delete/:id',
                                    'defaults' => [
                                        'action' => 'remove',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'service_manager' => [
        // This is not documented somewhere, but more specific classes need to go first
        'factories' => [
            ApiPrincipalAdapter::class => ApiPrincipalAdapterFactory::class,
            ApiAuthenticationService::class => ApiAuthenticationServiceFactory::class,
            UserService::class => UserServiceFactory::class,
            UserMapper::class => UserMapperFactory::class,
            PasswordInterface::class => PasswordFactory::class,
            AuthenticationService::class => AuthenticationServiceFactory::class,
            ApiPrincipalMapper::class => ApiPrincipalMapperFactory::class,
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
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'user' => __DIR__ . '/../view/',
        ],
    ],
    'doctrine' => [
        'driver' => [
            __NAMESPACE__ . '_driver' => [
                'class' => AttributeDriver::class,
                'paths' => [
                    __DIR__ . '/../src/Model/',
                ],
            ],
            'orm_default' => [
                'drivers' => [
                    __NAMESPACE__ . '\Model' => __NAMESPACE__ . '_driver',
                ],
            ],
        ],
        'authentication' => [
            'orm_default' => [
                'object_manager' => 'database_doctrine_em',
                'identity_class' => User::class,
                'identity_property' => 'login',
                'credential_property' => 'password',
            ],
        ],
    ],
];
