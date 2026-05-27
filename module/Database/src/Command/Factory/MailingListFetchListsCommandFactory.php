<?php

declare(strict_types=1);

namespace Database\Command\Factory;

use Database\Command\MailingListFetchListsCommand;
use Database\Service\Listmonk as ListmonkService;
use Database\Service\Mailman as MailmanService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MailingListFetchListsCommandFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MailingListFetchListsCommand {
        /** @var ListmonkService $listmonkService */
        $listmonkService = $container->get(ListmonkService::class);
        /** @var MailmanService $mailmanService */
        $mailmanService = $container->get(MailmanService::class);

        return new MailingListFetchListsCommand($listmonkService, $mailmanService);
    }
}
