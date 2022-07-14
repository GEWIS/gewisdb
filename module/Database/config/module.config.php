<?php

use Database\Controller\AddressController;
use Database\Controller\ExportController;
use Database\Controller\Factory\AddressControllerFactory;
use Database\Controller\Factory\ExportControllerFactory;
use Database\Controller\Factory\MeetingControllerFactory;
use Database\Controller\Factory\MemberControllerFactory;
use Database\Controller\Factory\OrganControllerFactory;
use Database\Controller\Factory\ProspectiveMemberControllerFactory;
use Database\Controller\Factory\QueryControllerFactory;
use Database\Controller\Factory\SettingsControllerFactory;
use Database\Controller\IndexController;
use Database\Controller\MeetingController;
use Database\Controller\MemberController;
use Database\Controller\OrganController;
use Database\Controller\ProspectiveMemberController;
use Database\Controller\QueryController;
use Database\Controller\SettingsController;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;

return array(
    'router' => array(
        'routes' => array(
            'home' => array(
                'type' => Literal::class,
                'options' => array(
                    'route'    => '/',
                    'defaults' => array(
                        'controller' => IndexController::class,
                        'action'     => 'index',
                    ),
                ),
            ),
            'address' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/address',
                    'defaults' => [
                        'controller' => AddressController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'meeting' => array(
                'type'    => Literal::class,
                'options' => array(
                    'route'    => '/meeting',
                    'defaults' => array(
                        'controller'    => MeetingController::class,
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'decision' => array(
                        'type' => Literal::class,
                        'options' => array(
                            'route' => '/decision',
                            'defaults' => array(
                                'action' => 'decision'
                            )
                        ),
                        'may_terminate' => false,
                        'child_routes' => array(
                            'form' => array(
                                'type' => Segment::class,
                                'options' => array(
                                    'route' => '/:form',
                                    'constraints' => array(
                                        'form' => '[a-zA-Z][a-zA-Z0-9_-]*'
                                    ),
                                    'defaults' => array(
                                        'action' => 'decisionform'
                                    )
                                )
                            ),
                            'create' => array(
                                'type' => Segment::class,
                                'options' => array(
                                    'route' => '/:type/:number/:point/:decision',
                                    'constraints' => array(
                                        'type' => 'AV|BV|VV|Virt',
                                        'number' => '[0-9]*',
                                        'point' => '[0-9]*',
                                        'decision' => '[0-9]*'
                                    )
                                )
                            ),
                            'delete' => array(
                                'type' => Segment::class,
                                'options' => array(
                                    'route' => '/delete/:type/:number/:point/:decision',
                                    'constraints' => array(
                                        'type' => 'AV|BV|VV|Virt',
                                        'number' => '[0-9]*',
                                        'point' => '[0-9]*',
                                        'decision' => '[0-9]*'
                                    ),
                                    'defaults' => array(
                                        'action' => 'delete'
                                    )
                                )
                            )
                        )
                    ),
                    'view' => array(
                        'type' => Segment::class,
                        'options' => array(
                            'route' => '/:type/:number',
                            'constraints' => array(
                                'type' => 'AV|BV|VV|Virt',
                                'number' => '\-?[0-9]*'
                            ),
                            'defaults' => array(
                                'action' => 'view'
                            )
                        )
                    ),
                    'create' => array(
                        'type' => Literal::class,
                        'options' => array(
                            'route' => '/create',
                            'defaults' => array(
                                'action' => 'create'
                            )
                        )
                    ),
                    'search' => array(
                        'type' => Literal::class,
                        'options' => array(
                            'route' => '/search',
                            'defaults' => array(
                                'action' => 'search'
                            )
                        )
                    )
                ),
            ),
            'organ' => array(
                'type'    => Literal::class,
                'options' => array(
                    'route'    => '/organ',
                    'defaults' => array(
                        'controller'    => OrganController::class,
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type'    => Segment::class,
                        'options' => array(
                            'route'    => '/:action',
                            'constraints' => array(
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                    'info' => array(
                        'type' => Segment::class,
                        'options' => array(
                            'route' => '/info/:type/:number/:point/:decision/:subdecision',
                            'defaults' => array(
                                'action' => 'info'
                            ),
                            'constraints' => array(
                                'type' => 'AV|BV|VV|Virt',
                                'number' => '[0-9]*',
                                'point' => '[0-9]*',
                                'decision' => '[0-9]*',
                                'subdecision' => '[0-9]*'
                            )
                        )
                    ),
                    'view' => array(
                        'type' => Segment::class,
                        'options' => array(
                            'route' => '/:type/:number/:point/:decision/:subdecision',
                            'defaults' => array(
                                'action' => 'view'
                            ),
                            'constraints' => array(
                                'type' => 'AV|BV|VV|Virt',
                                'number' => '[0-9]*',
                                'point' => '[0-9]*',
                                'decision' => '[0-9]*',
                                'subdecision' => '[0-9]*'
                            )
                        )
                    )
                ),
            ),
            'member' => array(
                'type'    => Literal::class,
                'options' => array(
                    'route'    => '/member',
                    'defaults' => array(
                        'controller'    => MemberController::class,
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'show' => array(
                        'type' => Segment::class,
                        'options' => array(
                            'route' => '/:id',
                            'constraints' => array(
                                'id' => '[0-9]+'
                            ),
                            'defaults' => array(
                                'action' => 'show'
                            )
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'edit' => array(
                                'type' => Literal::class,
                                'options' => array(
                                    'route' => '/edit',
                                    'defaults' => array(
                                        'action' => 'edit'
                                    )
                                ),
                                'may_terminate' => true,
                                'child_routes' => array(
                                    'address' => array(
                                        'type' => Segment::class,
                                        'options' => array(
                                            'route' => '/address/:type',
                                            'constraints' => array(
                                                'type' => 'home|student|mail'
                                            ),
                                            'defaults' => array(
                                                'action' => 'editAddress'
                                            ),
                                        )
                                    ),
                                    'membership' => array(
                                        'type' => Literal::class,
                                        'options' => array(
                                            'route' => '/membership',
                                            'defaults' => array(
                                                'action' => 'membership'
                                            )
                                        )
                                    ),
                                    'lists' => array(
                                        'type' => Literal::class,
                                        'options' => array(
                                            'route' => '/lists',
                                            'defaults' => array(
                                                'action' => 'lists'
                                            )
                                        )
                                    ),
                                    'expiration' => array(
                                        'type' => Literal::class,
                                        'options' => array(
                                            'route' => '/expiration',
                                            'defaults' => array(
                                                'action' => 'expiration'
                                            )
                                        )
                                    )
                                )
                            ),
                            'delete' => array(
                                'type' => Literal::class,
                                'options' => array(
                                    'route' => '/delete',
                                    'defaults' => array(
                                        'action' => 'delete'
                                    )
                                )
                            ),
                            'print' => array(
                                'type' => Literal::class,
                                'options' => array(
                                    'route' => '/print',
                                    'defaults' => array(
                                        'action' => 'print'
                                    )
                                )
                            ),
                            'remove-address' => array(
                                'type' => Segment::class,
                                'options' => array(
                                    'route' => '/remove/address/:type',
                                    'constraints' => array(
                                        'type' => 'home|student|mail'
                                    ),
                                    'defaults' => array(
                                        'action' => 'removeAddress'
                                    ),
                                )
                            ),
                            'add-address' => array(
                                'type' => Segment::class,
                                'options' => array(
                                    'route' => '/add/address/:type',
                                    'constraints' => array(
                                        'type' => 'home|student|mail'
                                    ),
                                    'defaults' => array(
                                        'action' => 'addAddress'
                                    ),
                                )
                            ),
                            'supremum' => array(
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/supremum',
                                    'defaults' => [
                                        'action' => 'setSupremum',
                                        'value' => ''
                                    ]
                                ],
                                'may_terminate' => true,
                                'child_routes' => [
                                    'optin' => [
                                        'type' => Literal::class,
                                        'options' => [
                                            'route' => '/optin',
                                            'defaults' => [
                                                'value' => 'optin'
                                            ]
                                        ]
                                    ],
                                    'optout' => [
                                        'type' => Literal::class,
                                        'options' => [
                                            'route' => '/optout',
                                            'defaults' => [
                                                'value' => 'optout'
                                            ]
                                        ]
                                    ],
                                ]
                            )
                        )
                    ),
                    'default' => array(
                        'type'    => Segment::class,
                        'options' => array(
                            'route'    => '/:action',
                            'constraints' => array(
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                ),
            ),
            'prospective-member' => array(
                'type'    => Literal::class,
                'options' => array(
                    'route'    => '/prospective-member',
                    'defaults' => array(
                        'controller'    => ProspectiveMemberController::class,
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'show' => array(
                        'type' => Segment::class,
                        'options' => array(
                            'route' => '/:id',
                            'constraints' => array(
                                'id' => '[0-9]+'
                            ),
                            'defaults' => array(
                                'action' => 'show'
                            )
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'delete' => array(
                                'type' => Literal::class,
                                'options' => array(
                                    'route' => '/delete',
                                    'defaults' => array(
                                        'action' => 'delete'
                                    )
                                )
                            ),
                            'finalize' => array(
                                'type' => Literal::class,
                                'options' => array(
                                    'route' => '/finalize',
                                    'defaults' => array(
                                        'action' => 'finalize'
                                    )
                                )
                            )
                        )
                    ),
                    'default' => array(
                        'type'    => Segment::class,
                        'options' => array(
                            'route'    => '/:action',
                            'constraints' => array(
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                ),
            ),
            'export' => array(
                'type'    => Literal::class,
                'options' => array(
                    'route'    => '/export',
                    'defaults' => array(
                        'controller'    => ExportController::class,
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type'    => Segment::class,
                        'options' => array(
                            'route'    => '/:action',
                            'constraints' => array(
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                ),
            ),
            'settings' => array(
                'type'    => Literal::class,
                'options' => array(
                    'route'    => '/settings',
                    'defaults' => array(
                        'controller'    => SettingsController::class,
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'list-delete' => array(
                        'type' => Segment::class,
                        'options' => array(
                            'route' => '/list/delete/:name',
                            'constraints' => array(
                                'name' => '[a-zA-Z0-9_-]+'
                            ),
                            'defaults' => array(
                                'action' => 'deleteList'
                            )
                        )
                    ),
                    'default' => array(
                        'type'    => Segment::class,
                        'options' => array(
                            'route'    => '/:action',
                            'constraints' => array(
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                ),
            ),
            'query' => array(
                'type'    => Literal::class,
                'options' => array(
                    'route'    => '/query',
                    'defaults' => array(
                        'controller'    => QueryController::class,
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'show' => array(
                        'type' => Segment::class,
                        'options' => array(
                            'route' => '/show/:query',
                            'constraints' => array(
                                'query' => '[0-9]+'
                            ),
                            'defaults' => array(
                                'action' => 'show'
                            )
                        )
                    ),
                    'default' => array(
                        'type'    => Segment::class,
                        'options' => array(
                            'route'    => '/:action',
                            'constraints' => array(
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'controllers' => array(
        'factories' => [
            AddressController::class => AddressControllerFactory::class,
            ExportController::class => ExportControllerFactory::class,
            MeetingController::class => MeetingControllerFactory::class,
            MemberController::class => MemberControllerFactory::class,
            OrganController::class => OrganControllerFactory::class,
            ProspectiveMemberController::class => ProspectiveMemberControllerFactory::class,
            QueryController::class => QueryControllerFactory::class,
            SettingsController::class => SettingsControllerFactory::class,
        ],
        'invokables' => array(
            IndexController::class => IndexController::class,
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'database' => __DIR__ . '/../view/'
        ),
        'strategies' => array(
            'ViewJsonStrategy'
        )
    ),
    'doctrine' => array(
        'driver' => array(
            'database_entities' => array(
                'class' => AnnotationDriver::class,
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../src/Model/')
            ),
            'orm_default' => array(
                'drivers' => array(
                    'Database\Model' => 'database_entities'
                ),
            )
        ),
        'configuration' => array(
            'orm_default' => array(
                'entity_namespaces' => array(
                    'dborig' => 'Database\Model'
                )
            )
        )
    )
);
