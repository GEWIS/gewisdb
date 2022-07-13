<?php

namespace Application;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Session\Container as SessionContainer;
use Zend\Validator\AbstractValidator;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        // add translator
        $translator = $e->getApplication()->getServiceManager()->get('translator');
        $translator->setLocale($this->determineLocale($e));

        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'logError']);
        $eventManager->attach(MvCEvent::EVENT_RENDER_ERROR, [$this, 'logError']);

        AbstractValidator::setDefaultTranslator($translator, 'validate');
    }

    /**
     * @param MvcEvent $e
     */
    public function logError($e)
    {
        $container = $e->getApplication()->getServiceManager();
        $logger = $container->get('logger');

        if ('error-router-no-match' === $e->getError()) {
            // not an interesting error
            return;
        }
        if ('error-exception' === $e->getError()) {
            $logger->error($e->getParam('exception'));

            return;
        }

        $logger->error($e->getError());
    }

    protected function determineLocale(MvcEvent $e)
    {
        $session = new SessionContainer('lang');
        if (!isset($session->lang)) {
            $session->lang = 'nl';
        }
        return $session->lang;
    }

    /**
     * Get the configuration for this module.
     *
     * @return array Module configuration
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Get service configuration.
     *
     * @return array Service configuration
     */
    public function getServiceConfig()
    {
        return [
            'invokables' => [
                'application_service_storage' => 'Application\Service\FileStorage',
            ],
            'factories' => [
                'logger' => function ($sm) {
                    $logger = new Logger('gewisdb');
                    $config = $sm->get('config')['logging'];

                    $handler = new RotatingFileHandler(
                        $config['logfile_path'],
                        $config['max_rotate_file_count'],
                        $config['minimal_log_level']
                    );
                    $logger->pushHandler($handler);

                    return $logger;
                },
            ],
        ];
    }

    /**
     * Get view helper configuration.
     *
     * @return array
     */
    public function getViewHelperConfig()
    {
        return [
            'factories' => [
                'fileUrl' => function ($sm) {
                    $locator = $sm->getServiceLocator();
                    $helper = new \Application\View\Helper\FileUrl();
                    $helper->setServiceLocator($locator);
                    return $helper;
                },
            ]
        ];
    }
}
