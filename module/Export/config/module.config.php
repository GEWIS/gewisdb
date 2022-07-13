<?php

use Export\Controller\ExportController;

return array(
    'controllers' => array(
        'invokables' => array(
            ExportController::class => ExportController::class,
        )
    ),
    'console' => array(
        'router' => array(
            'routes' => array(
                'export_old' => array(
                    'options' => array(
                        'route' => 'export old',
                        'defaults' => array(
                            'controller' => ExportController::class,
                            'action' => 'old'
                        )
                    )
                )
            )
        )
    )
);
