<?php

namespace Checker\Mapper\Factory;

use Checker\Mapper\Installation as InstallationMapper;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class InstallationFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return InstallationMapper
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): InstallationMapper {
        return new InstallationMapper(
            $container->get('database_doctrine_em')
        );
    }
}
