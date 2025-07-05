<?php

declare(strict_types=1);

namespace Report\Service\Factory;

use Database\Mapper\MailingList as MailingListMapper;
use Database\Mapper\MailingListMember as MailingListMemberMapper;
use Doctrine\ORM\EntityManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Report\Service\Misc as MiscService;

class MiscFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MiscService {
        /** @var MailingListMapper $mailingListMapper */
        $mailingListMapper = $container->get(MailingListMapper::class);
        /** @var MailingListMemberMapper $mailingListMemberMapper */
        $mailingListMemberMapper = $container->get(MailingListMemberMapper::class);
        /** @var EntityManager $emReport */
        $emReport = $container->get('doctrine.entitymanager.orm_report');

        return new MiscService(
            $mailingListMapper,
            $mailingListMemberMapper,
            $emReport,
        );
    }
}
