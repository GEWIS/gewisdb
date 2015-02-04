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
    $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] . 'member/subscribe';
}

/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
chdir(dirname(__DIR__));

// Setup autoloading
require 'init_autoloader.php';

// Run the application!
Zend\Mvc\Application::init(require 'config/application.config.php')->run();
