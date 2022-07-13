<?php

use Application\Controller\IndexController;
use Zend\Mvc\Router\Http\Segment;

return array(
    'router' => array(
        'routes' => array(
            'lang' => array(
                'type' => Segment::class,
                'options' => array(
                    'route' => '/lang/:lang',
                    'defaults' => array(
                        'controller' => IndexController::class,
                        'action' => 'lang',
                        'lang' => 'nl'
                    ),
                    'constraints' => array(
                        'lang' => '[a-zA-Z_]{2,5}'
                    )
                )
            ),
        ),
    ),
    'service_manager' => array(
        'abstract_factories' => array(
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ),
        'aliases' => array(
            'translator' => 'MvcTranslator'
        )
    ),
    // however counter-intuitive it is, leaving this in makes sure we do not
    // need the intl extension
    'translator' => array(
        'locale' => 'nl',
        'translation_file_patterns' => array(
            array(
                'type' => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo'
            ),
            // Zend\Validate translation
            array(
                'type' => 'phparray',
                'base_dir' => 'vendor/zendframework/zendframework/resources/languages/',
                'pattern' => '%s/Zend_Validate.php',
                'text_domain' => 'validate'
            )
        )
    ),
    'controllers' => array(
        'invokables' => array(
            IndexController::class => IndexController::class,
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => array(
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
);
