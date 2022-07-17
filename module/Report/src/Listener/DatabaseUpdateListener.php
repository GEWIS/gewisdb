<?php

namespace Report\Listener;

use Database\Model\{
    Address as DatabaseAddressModel,
    Decision as DatabaseDecisionModel,
    MailingList as DatabaseMailingListModel,
    Meeting as DatabaseMeetingModel,
    Member as DatabaseMemberModel,
    SubDecision as DatabaseSubDecisionModel,
};
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Report\Service\{
    Meeting as MeetingService,
    Member as MemberService,
    Misc as MiscService,
    Organ as OrganService,
};
use Report\Model\SubDecision\{
    Abrogation as ReportAbrogationModel,
    Discharge as ReportDischargeModel,
    Foundation as ReportFoundationModel,
    Installation as ReportInstallationModel,
};

/**
 * Doctrine event listener intended to automatically update reportdb.
 */
class DatabaseUpdateListener
{
    private MeetingService $meetingService;

    private MemberService $memberService;

    private MiscService $miscService;

    private OrganService $organService;

    private EntityManager $emReport;

    protected static bool $isflushing = false;

    public function __construct(
        MeetingService $meetingService,
        MemberService $memberService,
        MiscService $miscService,
        OrganService $organService,
        EntityManager $emReport,
    ) {
        $this->meetingService = $meetingService;
        $this->memberService = $memberService;
        $this->miscService = $miscService;
        $this->organService = $organService;
        $this->emReport = $emReport;
    }

    protected static function safeFlush(callable $func)
    {
        if (self::$isflushing) {
            return;
        }

        self::$isflushing = true;
        $func();
        self::$isflushing = false;
    }

    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        $this->postUpdate($eventArgs);
    }

    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();

        switch (true) {
            case $entity instanceof DatabaseAddressModel:
                $this->memberService->generateAddress($entity);
                break;

            case $entity instanceof DatabaseMemberModel:
                $this->memberService->generateMember($entity);
                break;

            case $entity instanceof DatabaseMeetingModel:
                $this->meetingService->generateMeeting($entity);
                break;

            case $entity instanceof DatabaseDecisionModel:
                $this->meetingService->generateDecision($entity);
                break;

            case $entity instanceof DatabaseSubDecisionModel:
                $subdecision = $this->meetingService->generateSubDecision($entity);
                $this->processOrganUpdates($subdecision);
                $this->emReport->persist($subdecision);
                break;

            case $entity instanceof DatabaseMailingListModel:
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
            case $entity instanceof ReportFoundationModel:
                $this->organService->generateFoundation($entity);
                break;

            case $entity instanceof ReportAbrogationModel:
                $this->organService->generateAbrogation($entity);
                break;

            case $entity instanceof ReportInstallationModel:
                $this->organService->generateInstallation($entity);
                break;

            case $entity instanceof ReportDischargeModel:
                $this->organService->generateDischarge($entity);
                break;
        }
    }
}
