<?php

namespace Report\Controller\Factory;

use Interop\Container\ContainerInterface;
use Report\Controller\ReportController;
use Report\Service\Board as BoardService;
use Report\Service\Meeting as MeetingService;
use Report\Service\Member as MemberService;
use Report\Service\Misc as MiscService;
use Report\Service\Organ as OrganService;
use Zend\ServiceManager\Factory\FactoryInterface;

class ReportControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return ReportController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): ReportController {
        /** @var BoardService $boardService */
        $boardService = $container->get(BoardService::class);
        /** @var MeetingService $meetingService */
        $meetingService = $container->get(MeetingService::class);
        /** @var MemberService $memberService */
        $memberService = $container->get(MemberService::class);
        /** @var MiscService $miscService */
        $miscService = $container->get(MiscService::class);
        /** @var OrganService $organService */
        $organService = $container->get(OrganService::class);

        return new ReportController(
            $boardService,
            $meetingService,
            $memberService,
            $miscService,
            $organService
        );
    }
}
