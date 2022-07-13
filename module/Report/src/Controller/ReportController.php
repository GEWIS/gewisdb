<?php

namespace Report\Controller;

use Report\Service\Board as BoardService;
use Report\Service\Meeting as MeetingService;
use Report\Service\Member as MemberService;
use Report\Service\Misc as MiscService;
use Report\Service\Organ as OrganService;
use Zend\Mvc\Controller\AbstractActionController;

class ReportController extends AbstractActionController
{
    /** @var BoardService $boardService */
    private $boardService;

    /** @var MeetingService $meetingService */
    private $meetingService;

    /** @var MemberService $memberService */
    private $memberService;

    /** @var MiscService $miscService */
    private $miscService;

    /** @var OrganService $organService */
    private $organService;

    /**
     * @param BoardService $boardService
     * @param MeetingService $meetingService
     * @param MemberService $memberService
     * @param MiscService $miscService
     * @param OrganService $organService
     */
    public function __construct(
        BoardService $boardService,
        MeetingService $meetingService,
        MemberService $memberService,
        MiscService $miscService,
        OrganService $organService
    ) {
        $this->boardService = $boardService;
        $this->meetingService = $meetingService;
        $this->memberService = $memberService;
        $this->miscService = $miscService;
        $this->organService = $organService;
    }

    /**
     * Generate reporting database.
     */
    public function generateAction()
    {
        echo "generating misc tables\n";
        $this->miscService->generate();

        echo "generating board tables\n";
        $this->boardService->generate();
    }

    public function generateAllAction()
    {
        echo "generating mailing list tables\n";
        $this->miscService->generate();

        echo "generating members table\n";
        $this->memberService->generate();

        echo "generating meetings and decision tables\n";
        $this->meetingService->generate();

        echo "generating organ tables\n";
        $this->organService->generate();

        echo "generating board tables\n";
        $this->boardService->generate();
    }
}
