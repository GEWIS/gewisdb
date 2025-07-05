<?php

declare(strict_types=1);

namespace Database\Command\Factory;

use Database\Command\MailmanFetchListsCommand;
use Database\Service\Mailman as MailmanService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MailmanFetchListsCommandFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MailmanFetchListsCommand {
        /** @var MailmanService $mailmanService */
        $mailmanService = $container->get(MailmanService::class);

        return new MailmanFetchListsCommand($mailmanService);
    }
}
