<?php

declare(strict_types=1);

namespace User\Mapper\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Mapper\UserMapper;

class UserMapperFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): UserMapper {
        return new UserMapper($container->get('doctrine.entitymanager.orm_default'));
    }
}
