<?php

namespace Report\Listener;

use Doctrine\ORM\EntityManager;
use Report\Service\Meeting as MeetingService;
use Report\Service\Member as MemberService;
use Report\Service\Misc as MiscService;
use Report\Service\Organ as OrganService;

/**
 * Doctrine event listener intended to automatically update reportdb.
 */
class DatabaseUpdateListener
{
    /** @var MeetingService $meetingService */
    private $meetingService;

    /** @var MemberService $memberService */
    private $memberService;

    /** @var MiscService $miscService */
    private $miscService;

    /** @var OrganService $organService */
    private $organService;

    /** @var EntityManager */
    private $emReport;

    /** @var bool $isflushing */
    protected static $isflushing = false;

    public function __construct(
        MeetingService $meetingService,
        MemberService $memberService,
        MiscService $miscService,
        OrganService $organService,
        EntityManager $emReport
    ) {
        $this->meetingService = $meetingService;
        $this->memberService = $memberService;
        $this->miscService = $miscService;
        $this->organService = $organService;
        $this->emReport = $emReport;
    }

    protected static function safeFlush($func)
    {
        if (self::$isflushing) {
            return;
        }
        self::$isflushing = true;
        $func();
        self::$isflushing = false;
    }

    public function postPersist($eventArgs)
    {
        $this->postUpdate($eventArgs);
    }

    public function postUpdate($eventArgs)
    {
        $entity = $eventArgs->getEntity();
        switch (true) {
            case $entity instanceof \Database\Model\Address:
                $this->memberService->generateAddress($entity);
                break;

            case $entity instanceof \Database\Model\Member:
                $this->memberService->generateMember($entity);
                break;

            case $entity instanceof \Database\Model\Meeting:
                $this->meetingService->generateMeeting($entity);
                break;

            case $entity instanceof \Database\Model\Decision:
                $this->meetingService->generateDecision($entity);
                break;

            case $entity instanceof \Database\Model\SubDecision:
                $subdecision = $this->meetingService->generateSubDecision($entity);
                $this->processOrganUpdates($subdecision);
                $this->emReport->persist($subdecision);
                break;

            case $entity instanceof \Database\Model\MailingList:
                $this->miscService->generateList($entity);
                break;

            default:
                return;
        }

        $em = $this->emReport;

        self::safeFlush(function () use ($em) {
            $em->flush();
        });
    }

    public function processOrganUpdates($entity)
    {
        switch (true) {
            case $entity instanceof \Report\Model\SubDecision\Foundation:
                $this->organService->generateFoundation($entity);
                break;

            case $entity instanceof \Report\Model\SubDecision\Abrogation:
                $this->organService->generateAbrogation($entity);
                break;

            case $entity instanceof \Report\Model\SubDecision\Installation:
                $this->organService->generateInstallation($entity);
                break;

            case $entity instanceof \Report\Model\SubDecision\Discharge:
                $this->organService->generateDischarge($entity);
                break;
        }
    }
}
