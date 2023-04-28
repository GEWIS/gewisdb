<?php

declare(strict_types=1);

namespace User\Service\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Adapter\ApiPrincipalAdapter;
use User\Service\ApiAuthenticationService;

class ApiAuthenticationServiceFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ApiAuthenticationService {
        $adapter = $container->get(ApiPrincipalAdapter::class);

        return new ApiAuthenticationService($adapter);
    }
}
