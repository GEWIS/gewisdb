<?php

use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Report\Controller\Factory\ReportControllerFactory;
use Report\Controller\ReportController;

return array(
    'controllers' => array(
        'factories' => array(
            ReportController::class => ReportControllerFactory::class,
        )
    ),
    'doctrine' => array(
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
                'filters' => array(),
                'entity_namespaces' => array(
                    'db' => 'Report\Model'
                )
            )
        ),
        'driver' => array(
            'Report_Driver' => array(
                'class' => AnnotationDriver::class,
                'cache' => 'array',
                'paths' => array(
                    __DIR__ . '/../src/Model'
                )
            ),
            'orm_report' => array(
                'class' => DriverChain::class,
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
        ),
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
                'generate_reportdb' => array(
                    'options' => array(
                        'route' => 'generate reportdb',
                        'defaults' => array(
                            'controller' => ReportController::class,
                            'action' => 'generate'
                        )
                    )
                ),
                'generate_reportdb_full' => array(
                    'options' => array(
                        'route' => 'generate reportdb full',
                        'defaults' => array(
                            'controller' => ReportController::class,
                            'action' => 'generateAll'
                        )
                    )
                )
            )
        )
    ),
    'mail' => []
);
