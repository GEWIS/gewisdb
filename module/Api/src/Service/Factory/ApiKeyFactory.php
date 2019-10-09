<?php

namespace Api\Service\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Api\Service\ApiKey as ApiKeyService;
use Api\Mapper\ApiKey as ApiKeyMapper;

class ApiKeyFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new ApiKeyService($sm->get(ApiKeyMapper::class));
    }
}
