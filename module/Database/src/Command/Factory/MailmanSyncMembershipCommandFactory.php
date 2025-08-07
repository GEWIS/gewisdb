<?php

declare(strict_types=1);

namespace Database\Command\Factory;

use Database\Command\MailmanSyncMembershipCommand;
use Database\Service\Mailman as MailmanService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MailmanSyncMembershipCommandFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MailmanSyncMembershipCommand {
        /** @var MailmanService $mailmanService */
        $mailmanService = $container->get(MailmanService::class);

        return new MailmanSyncMembershipCommand($mailmanService);
    }
}
