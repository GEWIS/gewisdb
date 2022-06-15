<?php

namespace User\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Crypt\Password\Bcrypt;

class PasswordFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Bcrypt([
            'cost' => 12
        ]);
    }
}
