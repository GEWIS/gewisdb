<?php

declare(strict_types=1);

// Use the production config, but apply test configs.
$applicationConfig = require __DIR__ . '/application.config.php';
$applicationConfig['module_listener_options']['config_glob_paths'] = [
    realpath(__DIR__) . '/autoload/{{,*.}global,{,*.}local}.test.php',
];

return $applicationConfig;
