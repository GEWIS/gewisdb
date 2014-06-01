<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Import\Controller\Import' => 'Import\Controller\ImportController'
        )
    ),
    'doctrine' => array(
        'driver' => array(
            'database_entities' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../src/Import/Model/')
            ),
            'orm_default' => array(
                'drivers' => array(
                    'Import\Model' => 'database_entities'
                )
            )
        )
    ),
    'console' => array(
        'router' => array(
            'routes' => array(
                'import' => array(
                    'options' => array(
                        'route' => 'import',
                        'defaults' => array(
                            'controller' => 'Import\Controller\Import',
                            'action' => 'import'
                        )
                    )
                )
            )
        )
    )
);
