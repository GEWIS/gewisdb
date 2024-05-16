<?php

declare(strict_types=1);

namespace Database\Controller\Factory;

use Database\Controller\ApiController;
use Database\Service\Api as ApiService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Service\ApiAuthenticationService;

class ApiControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ApiController {
        $apiService = $container->get(ApiService::class);
        $apiAuthService = $container->get(ApiAuthenticationService::class);

        return new ApiController(
            $apiService,
            $apiAuthService,
        );
    }
}
