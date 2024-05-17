<?php

declare(strict_types=1);

namespace Database\Mapper\Factory;

use Database\Mapper\Organ as OrganMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class OrganFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): OrganMapper {
        return new OrganMapper($container->get('doctrine.entitymanager.orm_default'));
    }
}
