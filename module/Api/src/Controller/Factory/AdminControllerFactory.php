<?php

namespace Api\Controller\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Api\Controller\AdminController;
use Api\Service\ApiKey;

class AdminControllerFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sl)
    {
        $sm = $sl->getServiceLocator();
        return new AdminController($sm->get(ApiKey::class));
    }
}
