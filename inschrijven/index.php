<?php
/**
 * Hack to force everyone to subscribe.
 */
/*echo '<pre>';
var_dump($_SERVER);
echo '</pre>';*/
$_SERVER['REQUEST_URI'] = '/~secr/database/inschrijven/member/subscribe';

/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
chdir(dirname(__DIR__));

// Setup autoloading
require 'init_autoloader.php';

// Run the application!
Zend\Mvc\Application::init(require 'config/application.config.php')->run();
