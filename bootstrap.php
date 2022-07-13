<?php

define('APP_ENV', getenv('APP_ENV') ?: 'production');

// make sure we are in the correct directory
chdir(__DIR__);

use Zend\Mvc\Application;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;

// Composer autoloading
include __DIR__ . '/vendor/autoload.php';

if (!class_exists(Application::class)) {
    throw new RuntimeException(
        "Unable to load application using composer autoloading.\n"
    );
}

class ConsoleRunner
{
    /**
     * @return array
     */
    public static function getConfig()
    {
        // Retrieve configuration
        $appConfig = require __DIR__ . '/config/application.config.php';
        if (APP_ENV === 'development' && file_exists(__DIR__ . '/config/development.config.php')) {
            $appConfig = ArrayUtils::merge($appConfig, require __DIR__ . '/config/development.config.php');
        }

        return $appConfig;
    }

    /**
     * @return Application
     */
    public static function getApplication()
    {
        // Retrieve configuration
        $appConfig = self::getConfig();

        // Initialise the application!
        return Application::init($appConfig);
    }

    /**
     * @return ServiceManager
     */
    public static function getServiceManager()
    {
        $appConfig = self::getConfig();

        $servicesConfig = $appConfig['service_manager'];
        if ($servicesConfig === null) {
            $servicesConfig = [];
        }

        $smConfig = new ServiceManagerConfig($servicesConfig);

        $serviceManager = new ServiceManager();
        $smConfig->configureServiceManager($serviceManager);
        $serviceManager->setService('ApplicationConfig', $appConfig);

        $moduleManager = $serviceManager->get('ModuleManager');
        $moduleManager->loadModules();

        return $serviceManager;
    }
}
