<?php

return [
    'controllers' => [
        'invokables' => [
            'Checker\Controller\Checker' => 'Checker\Controller\CheckerController'
        ]
    ],
    'console' => [
        'router' => [
            'routes' => [
                'check' => [
                    'options' => [
                        'route' => 'check database',
                        'defaults' => [
                            'controller' => 'Checker\Controller\Checker',
                            'action' => 'index'
                        ]
                    ]
                ],
                'check_memberships' => [
                    'options' => [
                        'route' => 'check memberships',
                        'defaults' => [
                            'controller' => 'Checker\Controller\Checker',
                            'action' => 'checkMemberships'
                        ]
                    ]
                ],
            ]
        ]
    ]
];
