<?php

declare(strict_types=1);

namespace Database\Service\Factory;

use Application\Service\Config as ConfigService;
use Database\Mapper\MailingList as MailingListMapper;
use Database\Mapper\MailingListMember as MailingListMemberMapper;
use Database\Mapper\MailmanMailingList as MailmanMailingListMapper;
use Database\Mapper\Member as MemberMapper;
use Database\Service\Mailman as MailmanService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MailmanFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MailmanService {
        /** @var MailingListMapper $mailingListMapper */
        $mailingListMapper = $container->get(MailingListMapper::class);
        /** @var MailmanMailingListMapper $mailmanMailingListMapper */
        $mailmanMailingListMapper = $container->get(MailmanMailingListMapper::class);
        /** @var MailingListMemberMapper $mailingListMemberMapper */
        $mailingListMemberMapper = $container->get(MailingListMemberMapper::class);
        /** @var MemberMapper $memberMapper */
        $memberMapper = $container->get(MemberMapper::class);
        /** @var ConfigService $configService */
        $configService = $container->get(ConfigService::class);
        /** @var array $mailmanConfig */
        $mailmanConfig = $container->get('config')['mailman_api'];

        return new MailmanService(
            $mailingListMapper,
            $mailmanMailingListMapper,
            $mailingListMemberMapper,
            $memberMapper,
            $configService,
            $mailmanConfig,
        );
    }
}
