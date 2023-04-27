<?php

declare(strict_types=1);

use Application\Controller\IndexController;
use Laminas\I18n\Translator\Resources;
use Laminas\Router\Http\Segment;
use User\Listener\AuthenticationListener;

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
                        'lang' => 'en',
                        'auth_type' => AuthenticationListener::AUTH_NONE,
                    ],
                    'constraints' => ['lang' => '[a-zA-Z_]{2,5}'],
                ],
            ],
        ],
    ],
    // however counter-intuitive it is, leaving this in makes sure we do not
    // need the intl extension
    'translator' => [
        'locale' => 'en',
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
    'view_helper_config' => [
        'flashmessenger' => [
            'message_open_format' => '<div%s><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button><ul><li>', // phpcs:ignore -- user-visible strings should not be split
            'message_close_string' => '</li></ul></div>',
            'message_separator_string' => '</li><li>',
        ],
    ],
];
