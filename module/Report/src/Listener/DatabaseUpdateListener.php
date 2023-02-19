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
    Keyholder as KeyholderService,
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
    Key\Granting as ReportKeyGrantingModel,
    Key\Withdrawal as ReportKeyWithdrawalModel
};

/**
 * Doctrine event listener intended to automatically update reportdb.
 */
class DatabaseUpdateListener
{
    protected static bool $isflushing = false;

    public function __construct(
        private readonly KeyholderService $keyholderService,
        private readonly MeetingService $meetingService,
        private readonly MemberService $memberService,
        private readonly MiscService $miscService,
        private readonly OrganService $organService,
        private readonly EntityManager $emReport,
    ) {
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
                $this->processKeyholderUpdates($subdecision);
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

    public function processOrganUpdates($entity): void
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

    public function processKeyholderUpdates($entity): void
    {
        switch (true) {
            case $entity instanceof ReportKeyGrantingModel:
                $this->keyholderService->generateGranting($entity);
                break;

            case $entity instanceof ReportKeyWithdrawalModel:
                $this->keyholderService->generateWithdrawal($entity);
                break;
        }
    }
}
