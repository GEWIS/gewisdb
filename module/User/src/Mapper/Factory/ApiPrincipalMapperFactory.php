<?php

declare(strict_types=1);

namespace User\Mapper\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;
use User\Mapper\ApiPrincipalMapper;

class ApiPrincipalMapperFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ApiPrincipalMapper {
        return new ApiPrincipalMapper($container->get('doctrine.entitymanager.orm_default'));
    }
}
