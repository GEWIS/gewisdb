<?php

namespace Checker\Service\Factory;

use Checker\Mapper\Installation as InstallationMapper;
use Checker\Service\Installation as InstallationService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

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
        array $options = null
    ): InstallationService {
        /** @var InstallationMapper $installationMapper */
        $installationMapper = $container->get(InstallationMapper::class);

        return new InstallationService($installationMapper);
    }
}
