<?php

namespace Checker\Mapper\Factory;

use Checker\Mapper\Organ as OrganMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class OrganFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return OrganMapper
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): OrganMapper {
        return new OrganMapper(
            $container->get('database_doctrine_em'),
        );
    }
}
