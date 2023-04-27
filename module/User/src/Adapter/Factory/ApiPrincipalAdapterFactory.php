<?php

declare(strict_types=1);

namespace User\Adapter\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Adapter\ApiPrincipalAdapter;
use User\Mapper\ApiPrincipalMapper;

class ApiPrincipalAdapterFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ApiPrincipalAdapter {
        return new ApiPrincipalAdapter(
            $container->get(ApiPrincipalMapper::class),
        );
    }
}
