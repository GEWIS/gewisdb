<?php

declare(strict_types=1);

namespace Database\Mapper\Factory;

use Database\Mapper\InstallationFunction as InstallationFunctionMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class InstallationFunctionFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): InstallationFunctionMapper {
        return new InstallationFunctionMapper($container->get('database_doctrine_em'));
    }
}
