<?php

namespace User\Service\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use User\Service\UserService;
use User\Mapper\UserMapper;
use User\Form\UserCreate;
use User\Form\Login;
use Zend\Authentication\AuthenticationService;
use Zend\Crypt\Password\PasswordInterface;

class UserServiceFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new UserService(
            $sm->get(UserMapper::class),
            $sm->get(UserCreate::class),
            $sm->get(Login::class),
            $sm->get(PasswordInterface::class),
            $sm->get(AuthenticationService::class)
        );
    }
}
