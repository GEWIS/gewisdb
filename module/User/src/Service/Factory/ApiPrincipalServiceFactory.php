<?php

declare(strict_types=1);

namespace User\Service\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Form\ApiPrincipal as ApiPrincipalForm;
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
        return new ApiPrincipalService(
            $container->get(ApiPrincipalForm::class),
            $container->get(ApiPrincipalMapper::class),
        );
    }
}
