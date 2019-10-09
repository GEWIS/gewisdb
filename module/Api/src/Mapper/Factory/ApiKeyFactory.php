<?php

namespace Api\Mapper\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Api\Mapper\ApiKey as ApiKeyMapper;

class ApiKeyFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new ApiKeyMapper($sm->get('database_doctrine_em'));
    }
}
