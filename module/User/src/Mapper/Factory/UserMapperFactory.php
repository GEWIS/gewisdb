<?php

namespace User\Mapper\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use User\Mapper\UserMapper;

class UserMapperFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): UserMapper {
        return new UserMapper($container->get('database_doctrine_em'));
    }
}
