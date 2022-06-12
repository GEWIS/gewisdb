<?php

return [
    'controllers' => [
        'invokables' => [
            'Export\Controller\Export' => 'Export\Controller\ExportController'
        ]
    ],
    'console' => [
        'router' => [
            'routes' => [
                'export_old' => [
                    'options' => [
                        'route' => 'export old',
                        'defaults' => [
                            'controller' => 'Export\Controller\Export',
                            'action' => 'old'
                        ]
                    ]
                ]
            ]
        ]
    ]
];
