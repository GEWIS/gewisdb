<?php
return array(
    'router' => array(
        'routes' => array(
            'database' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/database',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Database\Controller',
                        'controller'    => 'Database',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '[/:action]',
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
            'Database\Controller\Database' => 'Database\Controller\DatabaseController'
        )
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'database' => __DIR__ . '/../view/'
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
                )
            )
        )
    )
);
