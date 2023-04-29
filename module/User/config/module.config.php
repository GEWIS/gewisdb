<?php

declare(strict_types=1);

namespace User;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Laminas\Authentication\AuthenticationService;
use Laminas\Crypt\Password\PasswordInterface;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Psr\Container\ContainerInterface;
use User\Adapter\ApiPrincipalAdapter;
use User\Adapter\Factory\ApiPrincipalAdapterFactory;
use User\Controller\ApiSettingsController;
use User\Controller\Factory\ApiSettingsControllerFactory;
use User\Controller\Factory\SettingsControllerFactory;
use User\Controller\Factory\UserControllerFactory;
use User\Controller\SettingsController;
use User\Controller\UserController;
use User\Factory\PasswordFactory;
use User\Form\ApiPrincipal as ApiPrincipalForm;
use User\Form\Login;
use User\Form\UserCreate;
use User\Form\UserEdit;
use User\Listener\AuthenticationListener;
use User\Mapper\ApiPrincipalMapper;
use User\Mapper\Factory\ApiPrincipalMapperFactory;
use User\Mapper\Factory\UserMapperFactory;
use User\Mapper\UserMapper;
use User\Model\User;
use User\Service\ApiAuthenticationService;
use User\Service\ApiPrincipalService;
use User\Service\Factory\ApiAuthenticationServiceFactory;
use User\Service\Factory\ApiPrincipalServiceFactory;
use User\Service\Factory\AuthenticationServiceFactory;
use User\Service\Factory\UserServiceFactory;
use User\Service\UserService;

return [
    'router' => [
        'routes' => [
            'passwordlogin' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/login',
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
                                'action' => 'listUser',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'create' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/create',
                                    'defaults' => [
                                        'action' => 'createUser',
                                    ],
                                ],
                            ],
                            'edit' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/edit/:id',
                                    'defaults' => [
                                        'action' => 'editUser',
                                    ],
                                ],
                            ],
                            'delete' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/delete/:id',
                                    'defaults' => [
                                        'action' => 'removeUser',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'api-principals' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/api-principals',
                            'defaults' => [
                                'controller' => ApiSettingsController::class,
                                'action' => 'listPrincipals',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'create' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/create',
                                    'defaults' => [
                                        'controller' => ApiSettingsController::class,
                                        'action' => 'createPrincipal',
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
            ApiPrincipalService::class => ApiPrincipalServiceFactory::class,
            ApiAuthenticationService::class => ApiAuthenticationServiceFactory::class,
            UserService::class => UserServiceFactory::class,
            UserMapper::class => UserMapperFactory::class,
            PasswordInterface::class => PasswordFactory::class,
            AuthenticationService::class => AuthenticationServiceFactory::class,
            ApiPrincipalMapper::class => ApiPrincipalMapperFactory::class,
            ApiPrincipalForm::class => static function (ContainerInterface $container) {
                $form = new ApiPrincipalForm($container->get(MvcTranslator::class));
                $form->setHydrator($container->get('database_hydrator_default'));

                return $form;
            },
        ],
        'invokables' => [
            Login::class => Login::class,
            UserCreate::class => UserCreate::class,
            UserEdit::class => UserEdit::class,
        ],
    ],
    'controllers' => [
        'factories' => [
            ApiSettingsController::class => ApiSettingsControllerFactory::class,
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
