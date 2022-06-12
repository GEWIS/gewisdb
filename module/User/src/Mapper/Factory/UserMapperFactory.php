<?php

namespace User\Mapper\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use User\Mapper\UserMapper;

class UserMapperFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $sm)
    {
        return new UserMapper($sm->get('database_doctrine_em'));
    }
}
