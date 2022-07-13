<?php

use Checker\Controller\CheckerController;

return array(
    'controllers' => array(
        'invokables' => array(
            CheckerController::class => CheckerController::class
        )
    ),
    'console' => array(
        'router' => array(
            'routes' => array(
                'check' => array(
                    'options' => array(
                        'route' => 'check database',
                        'defaults' => array(
                            'controller' => CheckerController::class,
                            'action' => 'index'
                        )
                    )
                ),
                'check_memberships' => array(
                    'options' => array(
                        'route' => 'check memberships',
                        'defaults' => array(
                            'controller' => CheckerController::class,
                            'action' => 'checkMemberships'
                        )
                    )
                ),
                'check_discharges' => array(
                    'options' => array(
                        'route' => 'check discharges',
                        'defaults' => array(
                            'controller' => CheckerController::class,
                            'action' => 'checkDischarges'
                        )
                    )
                ),
            )
        )
    )
);
