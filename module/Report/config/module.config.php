<?php
return array(
    'controllers' => array(
        'invokables' => array(
            //'Export\Controller\Export' => 'Export\Controller\ExportController'
        )
    ),
    'doctrine' => array(
        'connection' => array(
            'orm_report' => array(),
        ),
        'configuration' => array(
            'orm_report' => array(
                'metadata_cache' => 'array',
                'query_cache' => 'array',
                'result_cache' => 'array',
                'hydration_cache' => 'array',
                'driver' => 'orm_report',
                'generate_proxies' => true,
                'proxy_dir' => 'data/DoctrineORMModule/Proxy',
                'proxy_namespace' => 'DoctrineORMModule\Proxy',
                'filters' => array()
            )
        ),
        'driver' => array(
            'Report_Driver' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(
                    __DIR__ . '/../src/Report/Model'
                )
            ),
            'orm_driver' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\DriverChain',
                'drivers' => array(
                    'Report\Model' => 'Report_Driver'
                )
            )
        ),
        'entitymanager' => array(
            'orm_report' => array(
                'connection' => 'orm_report',
                'configuration' => 'orm_report'
            )
        )
        'eventmanager' => array(
            'orm_report' => array()
        ),
        'sql_logger_collector' => array(
            'orm_report' => array()
        ),
        'entity_resolver' => array(
            'orm_report' => array()
        )
    ),
    'console' => array(
        'router' => array(
            'routes' => array(
                //'export_old' => array(
                    //'options' => array(
                        //'route' => 'export old',
                        //'defaults' => array(
                            //'controller' => 'Export\Controller\Export',
                            //'action' => 'old'
                        //)
                    //)
                //)
            )
        )
    )
);
