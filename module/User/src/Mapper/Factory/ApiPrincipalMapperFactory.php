<?php

namespace User\Mapper\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Mapper\ApiPrincipalMapper;

class ApiPrincipalMapperFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): ApiPrincipalMapper {
        return new ApiPrincipalMapper($container->get('database_doctrine_em'));
    }
}
