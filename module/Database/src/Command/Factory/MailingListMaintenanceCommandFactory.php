<?php

declare(strict_types=1);

namespace Database\Command\Factory;

use Database\Command\MailingListMaintenanceCommand;
use Database\Service\MailingList as MailingListService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MailingListMaintenanceCommandFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MailingListMaintenanceCommand {
        /** @var MailingListService $mailingListService */
        $mailingListService = $container->get(MailingListService::class);

        return new MailingListMaintenanceCommand($mailingListService);
    }
}
