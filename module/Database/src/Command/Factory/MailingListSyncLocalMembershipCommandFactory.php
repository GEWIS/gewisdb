<?php

declare(strict_types=1);

namespace Database\Command\Factory;

use Database\Command\MailingListSyncLocalMembershipCommand;
use Database\Service\MailingList as MailingListService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MailingListSyncLocalMembershipCommandFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MailingListSyncLocalMembershipCommand {
        /** @var MailingListService $mailingListService */
        $mailingListService = $container->get(MailingListService::class);

        return new MailingListSyncLocalMembershipCommand($mailingListService);
    }
}

