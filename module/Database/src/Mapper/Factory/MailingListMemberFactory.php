<?php

declare(strict_types=1);

namespace Database\Mapper\Factory;

use Database\Mapper\MailingListMember as MailingListMemberMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class MailingListMemberFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MailingListMemberMapper {
        return new MailingListMemberMapper($container->get('doctrine.entitymanager.orm_default'));
    }
}
