<?php

return [
    'router' => [
        'routes' => [
            'home' => [
                'type' => 'Literal',
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        '__NAMESPACE__' => 'Database\Controller',
                        'controller' => 'Index',
                        'action'     => 'index',
                    ],
                ],
            ],
            'address' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/address',
                    'defaults' => [
                        '__NAMESPACE__' => 'Database\Controller',
                        'controller' => 'Address',
                        'action' => 'index'
                    ]
                ]
            ],
            'meeting' => [
                'type'    => 'Literal',
                'options' => [
                    'route'    => '/meeting',
                    'defaults' => [
                        '__NAMESPACE__' => 'Database\Controller',
                        'controller'    => 'Meeting',
                        'action'        => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'decision' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/decision',
                            'defaults' => [
                                'action' => 'decision'
                            ]
                        ],
                        'may_terminate' => false,
                        'child_routes' => [
                            'form' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/:form',
                                    'constraints' => [
                                        'form' => '[a-zA-Z][a-zA-Z0-9_-]*'
                                    ],
                                    'defaults' => [
                                        'action' => 'decisionform'
                                    ]
                                ]
                            ],
                            'create' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/:type/:number/:point/:decision',
                                    'constraints' => [
                                        'type' => 'AV|BV|VV|Virt',
                                        'number' => '[0-9]*',
                                        'point' => '[0-9]*',
                                        'decision' => '[0-9]*'
                                    ]
                                ]
                            ],
                            'delete' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/delete/:type/:number/:point/:decision',
                                    'constraints' => [
                                        'type' => 'AV|BV|VV|Virt',
                                        'number' => '[0-9]*',
                                        'point' => '[0-9]*',
                                        'decision' => '[0-9]*'
                                    ],
                                    'defaults' => [
                                        'action' => 'delete'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'view' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/:type/:number',
                            'constraints' => [
                                'type' => 'AV|BV|VV|Virt',
                                'number' => '\-?[0-9]*'
                            ],
                            'defaults' => [
                                'action' => 'view'
                            ]
                        ]
                    ],
                    'create' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/create',
                            'defaults' => [
                                'action' => 'create'
                            ]
                        ]
                    ],
                    'search' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/search',
                            'defaults' => [
                                'action' => 'search'
                            ]
                        ]
                    ]
                ],
            ],
            'organ' => [
                'type'    => 'Literal',
                'options' => [
                    'route'    => '/organ',
                    'defaults' => [
                        '__NAMESPACE__' => 'Database\Controller',
                        'controller'    => 'Organ',
                        'action'        => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'default' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/:action',
                            'constraints' => [
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                    'info' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/info/:type/:number/:point/:decision/:subdecision',
                            'defaults' => [
                                'action' => 'info'
                            ],
                            'constraints' => [
                                'type' => 'AV|BV|VV|Virt',
                                'number' => '[0-9]*',
                                'point' => '[0-9]*',
                                'decision' => '[0-9]*',
                                'subdecision' => '[0-9]*'
                            ]
                        ]
                    ],
                    'view' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/:type/:number/:point/:decision/:subdecision',
                            'defaults' => [
                                'action' => 'view'
                            ],
                            'constraints' => [
                                'type' => 'AV|BV|VV|Virt',
                                'number' => '[0-9]*',
                                'point' => '[0-9]*',
                                'decision' => '[0-9]*',
                                'subdecision' => '[0-9]*'
                            ]
                        ]
                    ]
                ],
            ],
            'member' => [
                'type'    => 'Literal',
                'options' => [
                    'route'    => '/member',
                    'defaults' => [
                        '__NAMESPACE__' => 'Database\Controller',
                        'controller'    => 'Member',
                        'action'        => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'show' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/:id',
                            'constraints' => [
                                'id' => '[0-9]+'
                            ],
                            'defaults' => [
                                'action' => 'show'
                            ]
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'edit' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/edit',
                                    'defaults' => [
                                        'action' => 'edit'
                                    ]
                                ],
                                'may_terminate' => true,
                                'child_routes' => [
                                    'address' => [
                                        'type' => 'Segment',
                                        'options' => [
                                            'route' => '/address/:type',
                                            'constraints' => [
                                                'type' => 'home|student|mail'
                                            ],
                                            'defaults' => [
                                                'action' => 'editAddress'
                                            ],
                                        ]
                                    ],
                                    'membership' => [
                                        'type' => 'Literal',
                                        'options' => [
                                            'route' => '/membership',
                                            'defaults' => [
                                                'action' => 'membership'
                                            ]
                                        ]
                                    ],
                                    'lists' => [
                                        'type' => 'Literal',
                                        'options' => [
                                            'route' => '/lists',
                                            'defaults' => [
                                                'action' => 'lists'
                                            ]
                                        ]
                                    ],
                                    'expiration' => [
                                        'type' => 'Literal',
                                        'options' => [
                                            'route' => '/expiration',
                                            'defaults' => [
                                                'action' => 'expiration'
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            'delete' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/delete',
                                    'defaults' => [
                                        'action' => 'delete'
                                    ]
                                ]
                            ],
                            'print' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/print',
                                    'defaults' => [
                                        'action' => 'print'
                                    ]
                                ]
                            ],
                            'remove-address' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/remove/address/:type',
                                    'constraints' => [
                                        'type' => 'home|student|mail'
                                    ],
                                    'defaults' => [
                                        'action' => 'removeAddress'
                                    ],
                                ]
                            ],
                            'add-address' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/add/address/:type',
                                    'constraints' => [
                                        'type' => 'home|student|mail'
                                    ],
                                    'defaults' => [
                                        'action' => 'addAddress'
                                    ],
                                ]
                            ],
                            'supremum' => [
                                'type' => 'Literal',
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
                                        'type' => 'Literal',
                                        'options' => [
                                            'route' => '/optin',
                                            'defaults' => [
                                                'value' => 'optin'
                                            ]
                                        ]
                                    ],
                                    'optout' => [
                                        'type' => 'Literal',
                                        'options' => [
                                            'route' => '/optout',
                                            'defaults' => [
                                                'value' => 'optout'
                                            ]
                                        ]
                                    ],
                                ]
                            ]
                        ]
                    ],
                    'default' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/:action',
                            'constraints' => [
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                ],
            ],
            'prospective-member' => [
                'type'    => 'Literal',
                'options' => [
                    'route'    => '/prospective-member',
                    'defaults' => [
                        '__NAMESPACE__' => 'Database\Controller',
                        'controller'    => 'ProspectiveMember',
                        'action'        => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'show' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/:id',
                            'constraints' => [
                                'id' => '[0-9]+'
                            ],
                            'defaults' => [
                                'action' => 'show'
                            ]
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'delete' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/delete',
                                    'defaults' => [
                                        'action' => 'delete'
                                    ]
                                ]
                            ],
                            'finalize' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/finalize',
                                    'defaults' => [
                                        'action' => 'finalize'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'default' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/:action',
                            'constraints' => [
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                ],
            ],
            'export' => [
                'type'    => 'Literal',
                'options' => [
                    'route'    => '/export',
                    'defaults' => [
                        '__NAMESPACE__' => 'Database\Controller',
                        'controller'    => 'Export',
                        'action'        => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'default' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/:action',
                            'constraints' => [
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                ],
            ],
            'settings' => [
                'type'    => 'Literal',
                'options' => [
                    'route'    => '/settings',
                    'defaults' => [
                        '__NAMESPACE__' => 'Database\Controller',
                        'controller'    => 'Settings',
                        'action'        => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'list-delete' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/list/delete/:name',
                            'constraints' => [
                                'name' => '[a-zA-Z0-9_-]+'
                            ],
                            'defaults' => [
                                'action' => 'deleteList'
                            ]
                        ]
                    ],
                    'default' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/:action',
                            'constraints' => [
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                ],
            ],
            'query' => [
                'type'    => 'Literal',
                'options' => [
                    'route'    => '/query',
                    'defaults' => [
                        '__NAMESPACE__' => 'Database\Controller',
                        'controller'    => 'Query',
                        'action'        => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'show' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/show/:query',
                            'constraints' => [
                                'query' => '[0-9]+'
                            ],
                            'defaults' => [
                                'action' => 'show'
                            ]
                        ]
                    ],
                    'default' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/:action',
                            'constraints' => [
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Database\Controller\Meeting' => 'Database\Controller\MeetingController',
            'Database\Controller\ProspectiveMember' => 'Database\Controller\ProspectiveMemberController',
            'Database\Controller\Member' => 'Database\Controller\MemberController',
            'Database\Controller\Organ' => 'Database\Controller\OrganController',
            'Database\Controller\Export' => 'Database\Controller\ExportController',
            'Database\Controller\Query' => 'Database\Controller\QueryController',
            'Database\Controller\Settings' => 'Database\Controller\SettingsController',
            'Database\Controller\Address' => 'Database\Controller\AddressController',
            'Database\Controller\Index' => 'Database\Controller\IndexController',
        ]
    ],
    'view_manager' => [
        'template_path_stack' => [
            'database' => __DIR__ . '/../view/'
        ],
        'strategies' => [
            'ViewJsonStrategy'
        ]
    ],
    'doctrine' => [
        'driver' => [
            'database_entities' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Database/Model/']
            ],
            'orm_default' => [
                'drivers' => [
                    'Database\Model' => 'database_entities'
                ],
            ]
        ],
        'configuration' => [
            'orm_default' => [
                'entity_namespaces' => [
                    'dborig' => 'Database\Model'
                ]
            ]
        ]
    ]
];
