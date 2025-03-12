<?php

/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

declare(strict_types=1);

use Application\Extensions\Doctrine\Year;

return [
    /*
     * Doctrine global configuration, like functions
     */
    'doctrine' => [
        'configuration' => [
            'orm_report' => [
                'numeric_functions' => [
                    'YEAR' => Year::class,
                ],
            ],
        ],
    ],

    /**
     * Settings for storing files.
     */
    'storage' => [
        'storage_dir' => 'public/data',
        'public_dir' => 'data',
        'dir_mode' => 0o770,
    ],
    /*
     * Settings for Monolog logger
     */
    'logging' => [
        'logfile_path' => 'data/logs/gewisdb.log',
        'max_rotate_file_count' => 10,
        'minimal_log_level' => 'INFO',
    ],
];
