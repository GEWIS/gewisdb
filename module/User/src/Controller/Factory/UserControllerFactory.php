<?php

namespace User\Controller\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use User\Controller\UserController;
use User\Service\UserService;

class UserControllerFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sl)
    {
        $sm = $sl->getServiceLocator();
        return new UserController($sm->get(UserService::class));
    }
}
