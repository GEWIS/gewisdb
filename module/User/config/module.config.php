<?php
use User\Controller\UserController;

return [
    'router' => [
        'routers' => [
            'user' => [
                'type' => 'literal',
                'options' => [
                    'route' => '/user',
                    'defaults' => [
                        'controller' => UserController::class,
                        'action' => 'index'
                    ]
                ]
            ]
        ]
    ],
    'controllers' => [
        'invokables' => [
            UserController::class => UserController::class
        ]
    ]
];
