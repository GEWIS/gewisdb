<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

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

        AbstractValidator::setDefaultTranslator($translator, 'validate');
    }

    protected function determineLocale(MvcEvent $e)
    {
        $session = new SessionContainer('lang');
        if (!isset($session->lang)) {
            $session->lang = 'nl';
        }
        return $session->lang;
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ],
            ],
        ];
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
