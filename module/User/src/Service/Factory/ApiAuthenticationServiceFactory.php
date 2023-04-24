<?php

declare(strict_types=1);

namespace User\Service\Factory;

use User\Adapter\ApiPrincipalAdapter;
use User\Service\ApiAuthenticationService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Crypt\Password\PasswordInterface;
use Psr\Container\ContainerInterface;

class ApiAuthenticationServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return ApiAuthenticationService
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): ApiAuthenticationService {
        $adapter = $container->get(ApiPrincipalAdapter::class);
        return new ApiAuthenticationService($adapter);
    }
}
