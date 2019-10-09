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
            'Api\Controller\Index' => \Api\Controller\IndexController::class,
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
    'doctrine' => [
        'driver' => [
            'api_entities' => [
                'class' => \Doctrine\ORM\Mapping\Driver\AnnotationDriver::class,
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Model']
            ],
            'orm_default' => [
                'drivers' => [
                    'Api\Model' => 'api_entities'
                ]
            ]
        ],
    ]
];
