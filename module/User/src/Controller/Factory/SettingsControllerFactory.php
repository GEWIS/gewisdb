<?php

namespace User\Controller\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use User\Service\UserService;
use User\Controller\SettingsController;

class SettingsControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return SettingsController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): SettingsController {
        return new SettingsController($container->get(UserService::class));
    }
}
