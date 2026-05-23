<?php

declare(strict_types=1);

namespace Checker\Mapper\Factory;

use Checker\Mapper\Installation as InstallationMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class InstallationFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): InstallationMapper {
        return new InstallationMapper(
            $container->get('doctrine.entitymanager.orm_default'),
        );
    }
}
