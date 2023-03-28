<?php

namespace Database\Controller\Factory;

use Database\Controller\ApiController;
use Database\Service\Api as ApiService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Service\ApiAuthenticationService;

class ApiControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return ApiController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): ApiController {
        /** @var ApiService $apiService */
        $apiService = $container->get(ApiService::class);
        $apiAuthService = $container->get(ApiAuthenticationService::class);

        return new ApiController(
            $apiService,
            $apiAuthService,
        );
    }
}
