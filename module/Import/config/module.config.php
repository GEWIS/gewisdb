<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Import\Controller\Import' => 'Import\Controller\ImportController'
        )
    ),
    'console' => array(
        'router' => array(
            'routes' => array(
                'import_members' => array(
                    'options' => array(
                        'route' => 'import members',
                        'defaults' => array(
                            'controller' => 'Import\Controller\Import',
                            'action' => 'members'
                        )
                    )
                ),
                'import' => array(
                    'options' => array(
                        'route' => 'import',
                        'defaults' => array(
                            'controller' => 'Import\Controller\Import',
                            'action' => 'import'
                        )
                    )
                )
            )
        )
    )
);
