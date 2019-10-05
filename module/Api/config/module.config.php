<?php
return [
    'router' => [
        'routes' => [
            'api' => [
                'type' => 'Literal',
                'options' => [
                    'route'    => '/api',
                    'defaults' => [
                        '__NAMESPACE__' => 'Api\Controller',
                        'controller' => 'Index',
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Api\Controller\Index' => 'Api\Controller\IndexController',
        ]
    ],
    'view_manager' => [
        'template_path_stack' => [
            'api' => __DIR__ . '/../view/'
        ],
        'strategies' => [
            'ViewJsonStrategy'
        ]
    ],
];
