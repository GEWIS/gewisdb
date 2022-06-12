<?php

return [
    'controllers' => [
        'invokables' => [
            'Import\Controller\Import' => 'Import\Controller\ImportController'
        ]
    ],
    'console' => [
        'router' => [
            'routes' => [
                'import_members' => [
                    'options' => [
                        'route' => 'import members',
                        'defaults' => [
                            'controller' => 'Import\Controller\Import',
                            'action' => 'members'
                        ]
                    ]
                ],
                'import' => [
                    'options' => [
                        'route' => 'import',
                        'defaults' => [
                            'controller' => 'Import\Controller\Import',
                            'action' => 'import'
                        ]
                    ]
                ]
            ]
        ]
    ]
];
