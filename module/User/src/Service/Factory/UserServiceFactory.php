<?php

namespace User\Service\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use User\Service\UserService;
use User\Mapper\UserMapper;
use User\Form\UserCreate;

class UserServiceFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new UserService(
            $sm->get(UserMapper::class),
            $sm->get(UserCreate::class)
        );
    }
}
