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

return [
    // ...

    /**
     * Email configuration.
     */
    'email' => [
        'transport' => 'File',
        'options' => [
            'path' => 'data/mail/'
        ],
        'from' => 'secr@gewis.nl',
        'to' => [
            'report_error' => 'databaseisstuk@gewis.nl',
            'subscription' => 'secr@gewis.nl'
        ]
    ],
    'storage' => [
        'storage_dir' => 'public/data',
        'public_dir' => 'data',
        'dir_mode' => 0770,
    ],
];
