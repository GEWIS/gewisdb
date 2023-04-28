<?php

declare(strict_types=1);

namespace User\Service\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Mapper\ApiPrincipalMapper;
use User\Service\ApiPrincipalService;

class ApiPrincipalServiceFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ApiPrincipalService {
        $mapper = $container->get(ApiPrincipalMapper::class);

        return new ApiPrincipalService($mapper);
    }
}
