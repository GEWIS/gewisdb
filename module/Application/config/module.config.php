<?php

use Application\Controller\IndexController;
use Laminas\I18n\Translator\Resources;
use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'lang' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/lang/:lang',
                    'defaults' => [
                        'controller' => IndexController::class,
                        'action' => 'lang',
                        'lang' => 'nl',
                    ],
                    'constraints' => [
                        'lang' => '[a-zA-Z_]{2,5}',
                    ],
                ],
            ],
        ],
    ],
    'service_manager' => [
        'aliases' => [
            'translator' => 'MvcTranslator',
        ],
    ],
    // however counter-intuitive it is, leaving this in makes sure we do not
    // need the intl extension
    'translator' => [
        'locale' => 'nl',
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ],
            // Translations for Laminas\Validator.
            [
                'type' => 'phparray',
                'base_dir' => Resources::getBasePath(),
                'pattern' => Resources::getPatternForValidator(),
            ],
        ],
    ],
    'controllers' => [
        'invokables' => [
            IndexController::class => IndexController::class,
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
