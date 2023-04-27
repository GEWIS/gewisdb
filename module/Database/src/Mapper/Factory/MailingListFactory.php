<?php

declare(strict_types=1);

namespace Database\Mapper\Factory;

use Database\Mapper\MailingList as MailingListMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MailingListFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MailingListMapper {
        return new MailingListMapper($container->get('database_doctrine_em'));
    }
}
