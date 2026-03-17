<?php

declare(strict_types=1);

namespace Database\Command\Factory;

use Database\Command\ListmonkSyncMembershipCommand;
use Database\Service\Listmonk as ListmonkService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ListmonkSyncMembershipCommandFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ListmonkSyncMembershipCommand {
        /** @ @var ListmonkService $listmonkService */
        $listmonkService = $container->get(ListmonkService::class);

        return new ListmonkSyncMembershipCommand($listmonkService);
    }
}