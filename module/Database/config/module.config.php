<?php
return array(
    'router' => array(
        'routes' => array(
            'meeting' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/meeting',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Database\Controller',
                        'controller'    => 'Meeting',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'decision' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/decision',
                            'defaults' => array(
                                'action' => 'decision'
                            )
                        ),
                        'may_terminate' => false,
                        'child_routes' => array(
                            'form' => array(
                                'type' => 'Segment',
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
                                'type' => 'Segment',
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
                                'type' => 'Segment',
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
                        'type' => 'Segment',
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
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/create',
                            'defaults' => array(
                                'action' => 'create'
                            )
                        )
                    ),
                    'search' => array(
                        'type' => 'Literal',
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
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/organ',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Database\Controller',
                        'controller'    => 'Organ',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/:action',
                            'constraints' => array(
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                    'info' => array(
                        'type' => 'Segment',
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
                        'type' => 'Segment',
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
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/member',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Database\Controller',
                        'controller'    => 'Member',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'show' => array(
                        'type' => 'Segment',
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
                            'address' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/edit/address/:type',
                                    'constraints' => array(
                                        'type' => 'home|student|mail'
                                    ),
                                    'defaults' => array(
                                        'action' => 'editAddress'
                                    ),
                                )
                            ),
                            'edit' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/edit',
                                    'defaults' => array(
                                        'action' => 'edit'
                                    )
                                )
                            )
                        )
                    ),
                    'default' => array(
                        'type'    => 'Segment',
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
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/export',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Database\Controller',
                        'controller'    => 'Export',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type'    => 'Segment',
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
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/settings',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Database\Controller',
                        'controller'    => 'Settings',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type'    => 'Segment',
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
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/query',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Database\Controller',
                        'controller'    => 'Query',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'show' => array(
                        'type' => 'Segment',
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
                        'type'    => 'Segment',
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
        'invokables' => array(
            'Database\Controller\Meeting' => 'Database\Controller\MeetingController',
            'Database\Controller\Member' => 'Database\Controller\MemberController',
            'Database\Controller\Organ' => 'Database\Controller\OrganController',
            'Database\Controller\Export' => 'Database\Controller\ExportController',
            'Database\Controller\Query' => 'Database\Controller\QueryController',
            'Database\Controller\Settings' => 'Database\Controller\SettingsController',
        )
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
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../src/Database/Model/')
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
                    'db' => 'Database\Model'
                )
            )
        )
    )
);
