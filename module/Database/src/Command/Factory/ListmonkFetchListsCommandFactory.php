<?php

declare(strict_types=1);

namespace Database\Command\Factory;

use Database\Command\ListmonkFetchListsCommand;
use Database\Service\Listmonk as ListmonkService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ListmonkFetchListsCommandFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ListmonkFetchListsCommand {
        /** @var ListmonkService $listmonkService */
        $listmonkService = $container->get(ListmonkService::class);

        return new ListmonkFetchListsCommand($listmonkService);
    }
}