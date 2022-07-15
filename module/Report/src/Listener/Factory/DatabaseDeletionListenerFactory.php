<?php

namespace Report\Listener\Factory;

use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Report\Listener\DatabaseDeletionListener;
use Report\Service\Meeting as MeetingService;
use Report\Service\Member as MemberService;

class DatabaseDeletionListenerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return DatabaseDeletionListener
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): DatabaseDeletionListener {
        /** @var MeetingService $meetingService */
        $meetingService = $container->get(MeetingService::class);
        /** @var MemberService $memberService */
        $memberService = $container->get(MemberService::class);
        /** @var EntityManager */
        $emReport = $container->get('doctrine.entitymanager.orm_report');

        return new DatabaseDeletionListener(
            $meetingService,
            $memberService,
            $emReport,
        );
    }
}
