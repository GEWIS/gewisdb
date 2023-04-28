<?php

declare(strict_types=1);

namespace User\Controller\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Controller\SettingsController;
use User\Service\UserService;

class SettingsControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): SettingsController {
        return new SettingsController(
            $container->get(UserService::class),
            $container->get('config'),
        );
    }
}
