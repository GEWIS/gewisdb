<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'Export\Controller\Export' => 'Export\Controller\ExportController'
        )
    ),
    'console' => array(
        'router' => array(
            'routes' => array(
                'export_old' => array(
                    'options' => array(
                        'route' => 'export old',
                        'defaults' => array(
                            'controller' => 'Export\Controller\Export',
                            'action' => 'old'
                        )
                    )
                )
            )
        )
    )
);
