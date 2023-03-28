<?php

namespace User\Adapter\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Adapter\ApiPrincipalAdapter;
use User\Mapper\ApiPrincipalMapper;

class ApiPrincipalAdapterFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return ApiPrincipalAdapter
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): ApiPrincipalAdapter {
        return new ApiPrincipalAdapter(
            $container->get(ApiPrincipalMapper::class)
        );
    }
}
