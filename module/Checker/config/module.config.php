<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Checker\Controller\Checker' => 'Checker\Controller\CheckerController'
        )
    ),
    'console' => array(
        'router' => array(
            'routes' => array(
                'check' => array(
                    'options' => array(
                        'route' => 'check database',
                        'defaults' => array(
                            'controller' => 'Checker\Controller\Checker',
                            'action' => 'index'
                        )
                    )
                ),
            )
        )
    )
);
