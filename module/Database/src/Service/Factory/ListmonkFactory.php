<?php

declare(strict_types=1);

namespace Database\Service\Factory;

use Application\Service\Config as ConfigService;
use Database\Mapper\ListmonkMailingList as ListmonkMailingListMapper;
use Database\Mapper\MailingList as MailingListMapper;
use Database\Mapper\MailingListMember as MailingListMemberMapper;
use Database\Mapper\Member as MemberMapper;
use Database\Service\Listmonk as ListmonkService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ListmonkFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ListmonkService {
        /** @var MailingListMapper $mailingListMapper */
        $mailingListMapper = $container->get(MailingListMapper::class);
        /** @var ListmonkMailingListMapper $listmonkMailingListMapper */
        $listmonkMailingListMapper = $container->get(ListmonkMailingListMapper::class);
        /** @var MailingListMemberMapper $mailingListMemberMapper */
        $mailingListMemberMapper = $container->get(MailingListMemberMapper::class);
        /** @var MemberMapper $memberMapper */
        $memberMapper = $container->get(MemberMapper::class);
        /** @var ConfigService $configService */
        $configService = $container->get(ConfigService::class);
        /** @var array $listmonkConfig */
        $listmonkConfig = $container->get('config')['listmonk_api'];

        return new ListmonkService(
            $mailingListMapper,
            $listmonkMailingListMapper,
            $mailingListMemberMapper,
            $memberMapper,
            $configService,
            $listmonkConfig,
        );
    }
}