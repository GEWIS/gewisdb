<?php

declare(strict_types=1);

namespace Checker\Service\Factory;

use Checker\Mapper\Installation as InstallationMapper;
use Checker\Service\Installation as InstallationService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class InstallationFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): InstallationService {
        /** @var InstallationMapper $installationMapper */
        $installationMapper = $container->get(InstallationMapper::class);

        return new InstallationService($installationMapper);
    }
}
