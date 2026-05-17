<?php

declare(strict_types=1);

namespace Database\Controller\Factory;

use Database\Controller\QueryController;
use Database\Service\Query as QueryService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class QueryControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): QueryController {
        /** @var QueryService $queryService */
        $queryService = $container->get(QueryService::class);

        return new QueryController($queryService);
    }
}
