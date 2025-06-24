<?php

/**
 * List of enabled modules for this application.
 *
 * This should be an array of module namespaces used in the application.
 */

declare(strict_types=1);

return [
    'Laminas\I18n',
    'Laminas\Mvc\I18n',
    'Laminas\Mvc\Plugin\FlashMessenger',
    'Laminas\Mvc\Plugin\Identity',
    'Laminas\Serializer',
    'Laminas\Mail',
    'Laminas\Cache',
    'Laminas\InputFilter',
    'Laminas\Paginator',
    'Laminas\Hydrator',
    'Laminas\Form',
    'Laminas\Router',
    'Laminas\Validator',
    'DoctrineModule',
    'DoctrineORMModule',
    'Laminas\Cache\Storage\Adapter\Filesystem',
    'Laminas\Cache\Storage\Adapter\Memory',
    'Application',
    'Database',
    'Checker',
    'Report',
    'User',
];
