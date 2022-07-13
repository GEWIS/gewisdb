<?php

use Checker\Controller\CheckerController;
use Checker\Controller\Factory\CheckerControllerFactory;

return array(
    'controllers' => array(
        'factories' => array(
            CheckerController::class => CheckerControllerFactory::class
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
