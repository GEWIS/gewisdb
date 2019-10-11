<?php

namespace User\Service\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use User\Service\UserService;
use User\Mapper\UserMapper;
use User\Form\UserCreate;
use User\Form\Login;
use Zend\Crypt\Password\Bcrypt;

class UserServiceFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        $bcrypt = new Bcrypt([
            'cost' => 12
        ]);
        return new UserService(
            $sm->get(UserMapper::class),
            $sm->get(UserCreate::class),
            $sm->get(Login::class),
            $bcrypt
        );
    }
}
