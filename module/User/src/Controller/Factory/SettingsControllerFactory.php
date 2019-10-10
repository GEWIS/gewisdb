<?php

namespace User\Controller\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use User\Service\UserService;
use User\Controller\SettingsController;

class SettingsControllerFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sl)
    {
        $sm = $sl->getServiceLocator();
        return new SettingsController($sm->get(UserService::class));
    }
}
