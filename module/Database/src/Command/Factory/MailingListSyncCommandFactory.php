<?php

declare(strict_types=1);

namespace Database\Command\Factory;

use Database\Command\MailingListSyncCommand;
use Database\Service\Listmonk as ListmonkService;
use Database\Service\MailingList as MailingListService;
use Database\Service\Mailman as MailmanService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MailingListSyncCommandFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MailingListSyncCommand {
        /** @var MailingListService $mailingListService */
        $mailingListService = $container->get(MailingListService::class);
        /** @var MailmanService $mailmanService */
        $mailmanService = $container->get(MailmanService::class);
        /** @var ListmonkService $listmonkService */
        $listmonkService = $container->get(ListmonkService::class);

        return new MailingListSyncCommand($mailingListService, $mailmanService, $listmonkService);
    }
}
