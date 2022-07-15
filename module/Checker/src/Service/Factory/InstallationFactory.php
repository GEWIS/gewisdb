<?php

namespace Checker\Service\Factory;

use Checker\Mapper\Installation as InstallationMapper;
use Checker\Service\Installation as InstallationService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class InstallationFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return InstallationService
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): InstallationService {
        /** @var InstallationMapper $installationMapper */
        $installationMapper = $container->get(InstallationMapper::class);

        return new InstallationService($installationMapper);
    }
}
