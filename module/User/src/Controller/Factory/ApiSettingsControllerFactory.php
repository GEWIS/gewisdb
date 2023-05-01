<?php

declare(strict_types=1);

namespace User\Controller\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Controller\ApiSettingsController;
use User\Service\ApiPrincipalService;

class ApiSettingsControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ApiSettingsController {
        return new ApiSettingsController(
            $container->get(ApiPrincipalService::class),
            $container->get('config'),
        );
    }
}
