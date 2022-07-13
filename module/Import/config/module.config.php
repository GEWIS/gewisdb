<?php

use Import\Controller\ImportController;

return array(
    'controllers' => array(
        'invokables' => array(
            ImportController::class => ImportController::class,
        )
    ),
    'console' => array(
        'router' => array(
            'routes' => array(
                'import_members' => array(
                    'options' => array(
                        'route' => 'import members',
                        'defaults' => array(
                            'controller' => ImportController::class,
                            'action' => 'members'
                        )
                    )
                ),
                'import' => array(
                    'options' => array(
                        'route' => 'import',
                        'defaults' => array(
                            'controller' => ImportController::class,
                            'action' => 'import'
                        )
                    )
                )
            )
        )
    )
);
