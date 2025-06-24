<?php

declare(strict_types=1);

namespace Database\Command\Factory;

use Database\Command\FetchMailmanListsCommand;
use Database\Service\Mailman as MailmanService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class FetchMailmanListsCommandFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): FetchMailmanListsCommand {
        /** @var MailmanService $mailmanService */
        $mailmanService = $container->get(MailmanService::class);

        return new FetchMailmanListsCommand($mailmanService);
    }
}
