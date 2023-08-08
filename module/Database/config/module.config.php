<?php

declare(strict_types=1);

namespace Database;

use Database\Command\DeleteExpiredMembersCommand;
use Database\Command\DeleteExpiredProspectiveMembersCommand;
use Database\Command\GenerateAuthenticationKeysCommand;
use Database\Controller\ApiController;
use Database\Controller\ExportController;
use Database\Controller\Factory\ApiControllerFactory;
use Database\Controller\Factory\ExportControllerFactory;
use Database\Controller\Factory\IndexControllerFactory;
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
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Method;
use Laminas\Router\Http\Segment;
use User\Listener\AuthenticationListener;

return [
    'router' => [
        'routes' => [
            'home' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/',
                    'defaults' => [
                        'controller' => IndexController::class,
                        'action' => 'index',
                    ],
                ],
            ],
            'meeting' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/meeting',
                    'defaults' => [
                        'controller' => MeetingController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'decision' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/decision',
                            'defaults' => [
                                'action' => 'decision',
                            ],
                        ],
                        'may_terminate' => false,
                        'child_routes' => [
                            'form' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/:form',
                                    'constraints' => [
                                        'form' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ],
                                    'defaults' => [
                                        'action' => 'decisionform',
                                    ],
                                ],
                            ],
                            'create' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/:type/:number/:point/:decision',
                                    'constraints' => [
                                        'type' => 'ALV|BV|VV|Virt',
                                        'number' => '[0-9]*',
                                        'point' => '[0-9]*',
                                        'decision' => '[0-9]*',
                                    ],
                                ],
                            ],
                            'delete' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/delete/:type/:number/:point/:decision',
                                    'constraints' => [
                                        'type' => 'ALV|BV|VV|Virt',
                                        'number' => '[0-9]*',
                                        'point' => '[0-9]*',
                                        'decision' => '[0-9]*',
                                    ],
                                    'defaults' => [
                                        'action' => 'delete',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'view' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:type/:number',
                            'constraints' => [
                                'type' => 'ALV|BV|VV|Virt',
                                'number' => '\-?[0-9]*',
                            ],
                            'defaults' => [
                                'action' => 'view',
                            ],
                        ],
                    ],
                    'create' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/create',
                            'defaults' => [
                                'action' => 'create',
                            ],
                        ],
                    ],
                    'search' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/search',
                            'defaults' => [
                                'action' => 'search',
                            ],
                        ],
                    ],
                ],
            ],
            'organ' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/organ',
                    'defaults' => [
                        'controller' => OrganController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'default' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:action',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                    'info' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/info/:type/:number/:point/:decision/:subdecision',
                            'defaults' => ['action' => 'info'],
                            'constraints' => [
                                'type' => 'ALV|BV|VV|Virt',
                                'number' => '[0-9]*',
                                'point' => '[0-9]*',
                                'decision' => '[0-9]*',
                                'subdecision' => '[0-9]*',
                            ],
                        ],
                    ],
                    'view' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:type/:number/:point/:decision/:subdecision',
                            'defaults' => ['action' => 'view'],
                            'constraints' => [
                                'type' => 'ALV|BV|VV|Virt',
                                'number' => '[0-9]*',
                                'point' => '[0-9]*',
                                'decision' => '[0-9]*',
                                'subdecision' => '[0-9]*',
                            ],
                        ],
                    ],
                ],
            ],
            'member' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/member',
                    'defaults' => [
                        'controller' => MemberController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'show' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:id',
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'show',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'edit' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/edit',
                                    'defaults' => [
                                        'action' => 'edit',
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes' => [
                                    'address' => [
                                        'type' => Segment::class,
                                        'options' => [
                                            'route' => '/address/:type',
                                            'constraints' => [
                                                'type' => 'home|student|mail',
                                            ],
                                            'defaults' => [
                                                'action' => 'editAddress',
                                            ],
                                        ],
                                    ],
                                    'membership' => [
                                        'type' => Literal::class,
                                        'options' => [
                                            'route' => '/membership',
                                            'defaults' => [
                                                'action' => 'membership',
                                            ],
                                        ],
                                    ],
                                    'lists' => [
                                        'type' => Literal::class,
                                        'options' => [
                                            'route' => '/lists',
                                            'defaults' => [
                                                'action' => 'lists',
                                            ],
                                        ],
                                    ],
                                    'expiration' => [
                                        'type' => Literal::class,
                                        'options' => [
                                            'route' => '/expiration',
                                            'defaults' => [
                                                'action' => 'expiration',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'update' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/update',
                                    'defaults' => [
                                        'action' => 'showUpdate',
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes' => [
                                    'approve' => [
                                        'type' => Literal::class,
                                        'options' => [
                                            'route' => '/approve',
                                            'defaults' => [
                                                'action' => 'approveUpdate',
                                            ],
                                        ],
                                    ],
                                    'reject' => [
                                        'type' => Literal::class,
                                        'options' => [
                                            'route' => '/reject',
                                            'defaults' => [
                                                'action' => 'rejectUpdate',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'delete' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/delete',
                                    'defaults' => [
                                        'action' => 'delete',
                                    ],
                                ],
                            ],
                            'remove-address' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/remove/address/:type',
                                    'constraints' => [
                                        'type' => 'home|student|mail',
                                    ],
                                    'defaults' => [
                                        'action' => 'removeAddress',
                                    ],
                                ],
                            ],
                            'add-address' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/add/address/:type',
                                    'constraints' => [
                                        'type' => 'home|student|mail',
                                    ],
                                    'defaults' => [
                                        'action' => 'addAddress',
                                    ],
                                ],
                            ],
                            'supremum' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/supremum',
                                    'defaults' => [
                                        'action' => 'setSupremum',
                                        'value' => '',
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes' => [
                                    'optin' => [
                                        'type' => Literal::class,
                                        'options' => [
                                            'route' => '/optin',
                                            'defaults' => [
                                                'value' => 'optin',
                                            ],
                                        ],
                                    ],
                                    'optout' => [
                                        'type' => Literal::class,
                                        'options' => [
                                            'route' => '/optout',
                                            'defaults' => [
                                                'value' => 'optout',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'renew' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/renew/:token',
                            'constraints' => [
                                'token' => '[a-zA-Z0-9\_\-\+]+',
                            ],
                            'defaults' => [
                                'action' => 'renew',
                                'auth_type' => AuthenticationListener::AUTH_NONE,
                            ],
                        ],
                    ],
                    'subscribe' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/subscribe',
                            'defaults' => [
                                'action' => 'subscribe',
                                'auth_type' => AuthenticationListener::AUTH_NONE,
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'checkout' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/checkout',
                                ],
                                'may_terminate' => false,
                                'child_routes' => [
                                    'status' => [
                                        'type' => Segment::class,
                                        'options' => [
                                            'route' => '/:status',
                                            'defaults' => [
                                                'action' => 'checkoutStatus',
                                                'constraints' => [
                                                    'status' => 'cancelled|completed|error',
                                                ],
                                            ],
                                        ],
                                    ],
                                    'restart' => [
                                        'type' => Segment::class,
                                        'options' => [
                                            'route' => '/restart/:token',
                                            'defaults' => [
                                                'action' => 'checkoutRestart',
                                                'constraints' => [
                                                    'id' => '[a-zA-Z0-9\_\-\+]+',
                                                ],
                                            ],
                                        ],
                                    ],
                                    'webhook' => [
                                        'type' => Literal::class,
                                        'options' => [
                                            'route' => '/webhook',
                                        ],
                                        'may_terminate' => false,
                                        'child_routes' => [
                                            'webhook_post' => [
                                                'type' => Method::class,
                                                'options' => [
                                                    'verb' => 'POST',
                                                    'defaults' => [
                                                        'action' => 'paymentWebhook',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'search' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/search',
                            'defaults' => [
                                'action' => 'search',
                            ],
                        ],
                    ],
                    'searchFiltered' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/searchFiltered',
                            'defaults' => [
                                'action' => 'searchFiltered',
                            ],
                        ],
                    ],
                    'tuelookup' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/tuelookup',
                            'defaults' => [
                                'action' => 'tueLookup',
                            ],
                        ],
                    ],
                    'tuerequest' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/tuerequest',
                            'defaults' => [
                                'action' => 'tueRequest',
                            ],
                        ],
                    ],
                    'updates' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/updates',
                            'defaults' => [
                                'action' => 'updates',
                            ],
                        ],
                    ],
                ],
            ],
            'prospective-member' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/prospective-member',
                    'defaults' => [
                        'controller' => ProspectiveMemberController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'show' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:id',
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'show',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'delete' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/delete',
                                    'defaults' => [
                                        'action' => 'delete',
                                    ],
                                ],
                            ],
                            'finalize' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/finalize',
                                    'defaults' => [
                                        'action' => 'finalize',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'default' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:action',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                ],
            ],
            'export' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/export',
                    'defaults' => [
                        'controller' => ExportController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'default' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:action',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                ],
            ],
            'settings' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/settings',
                    'defaults' => [
                        'controller' => SettingsController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'list-delete' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/list/delete/:name',
                            'constraints' => [
                                'name' => '[a-zA-Z0-9_-]+',
                            ],
                            'defaults' => [
                                'action' => 'deleteList',
                            ],
                        ],
                    ],
                    'default' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:action',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                ],
            ],
            'query' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/query',
                    'defaults' => [
                        'controller' => QueryController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'show' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/show/:query',
                            'constraints' => [
                                'query' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'show',
                            ],
                        ],
                    ],
                    'export' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/export',
                            'defaults' => [
                                'action' => 'export',
                            ],
                        ],
                    ],
                ],
            ],
            'api' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/api',
                    'defaults' => [
                        'controller' => ApiController::class,
                        'action' => 'healthy',
                        'auth_type' => AuthenticationListener::AUTH_API,
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'members' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/members',
                            'defaults' => [
                                'controller' => ApiController::class,
                                'action' => 'members',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'active' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/active',
                                    'defaults' => [
                                        'action' => 'membersActive',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'member' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/members/:id',
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'member',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            ApiController::class => ApiControllerFactory::class,
            ExportController::class => ExportControllerFactory::class,
            IndexController::class => IndexControllerFactory::class,
            MeetingController::class => MeetingControllerFactory::class,
            MemberController::class => MemberControllerFactory::class,
            OrganController::class => OrganControllerFactory::class,
            ProspectiveMemberController::class => ProspectiveMemberControllerFactory::class,
            QueryController::class => QueryControllerFactory::class,
            SettingsController::class => SettingsControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'database' => __DIR__ . '/../view/',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
    'laminas-cli' => [
        'commands' => [
            'database:members:delete-expired' => DeleteExpiredMembersCommand::class,
            'database:members:generate-keys' => GenerateAuthenticationKeysCommand::class,
            'database:prospective-members:delete-expired' => DeleteExpiredProspectiveMembersCommand::class,
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
    ],
];
