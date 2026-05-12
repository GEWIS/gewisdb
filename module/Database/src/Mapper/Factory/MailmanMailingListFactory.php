<?php

declare(strict_types=1);

namespace Database\Mapper\Factory;

use Database\Mapper\MailmanMailingList as MailmanMailingListMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class MailmanMailingListFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MailmanMailingListMapper {
        return new MailmanMailingListMapper($container->get('doctrine.entitymanager.orm_default'));
    }
}
