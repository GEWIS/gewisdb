<?php

/**
 * Hack to force everyone to subscribe.
 */
if (isset($_SERVER['REDIRECT_BASE']) && !empty($_SERVER['REDIRECT_BASE'])) {
    // allow language switch
    if (!preg_match('#/lang/[a-zA-Z_]{2,5}$#', $_SERVER['REQUEST_URI'])) {
        $_SERVER['REQUEST_URI'] = $_SERVER['REDIRECT_BASE'] . 'member/subscribe';
    }
} else {
    if (!preg_match('#/lang/[a-zA-Z_]{2,5}$#', $_SERVER['REQUEST_URI'])) {
        $_SERVER['REQUEST_URI'] = '/member/subscribe';
    }
}

/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
chdir(dirname(__DIR__));

// Decline static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server') {
    $path = realpath(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if (is_string($path) && __FILE__ !== $path && is_file($path)) {
        return false;
    }
    unset($path);
}

require 'bootstrap.php';

$application = ConsoleRunner::getApplication();
$application->run();
