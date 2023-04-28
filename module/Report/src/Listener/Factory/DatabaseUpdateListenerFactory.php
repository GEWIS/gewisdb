<?php

declare(strict_types=1);

namespace Report\Listener\Factory;

use Doctrine\ORM\EntityManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Report\Listener\DatabaseUpdateListener;
use Report\Service\Board as BoardService;
use Report\Service\Keyholder as KeyholderService;
use Report\Service\Meeting as MeetingService;
use Report\Service\Member as MemberService;
use Report\Service\Misc as MiscService;
use Report\Service\Organ as OrganService;

class DatabaseUpdateListenerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): DatabaseUpdateListener {
        /** @var BoardService $boardService */
        $boardService = $container->get(BoardService::class);
        /** @var KeyholderService $keyholderService */
        $keyholderService = $container->get(KeyholderService::class);
        /** @var MeetingService $meetingService */
        $meetingService = $container->get(MeetingService::class);
        /** @var MemberService $memberService */
        $memberService = $container->get(MemberService::class);
        /** @var MiscService $miscService */
        $miscService = $container->get(MiscService::class);
        /** @var OrganService $organService */
        $organService = $container->get(OrganService::class);
        /** @var EntityManager $emReport */
        $emReport = $container->get('doctrine.entitymanager.orm_report');

        return new DatabaseUpdateListener(
            $boardService,
            $keyholderService,
            $meetingService,
            $memberService,
            $miscService,
            $organService,
            $emReport,
        );
    }
}
