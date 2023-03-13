<?php

declare(strict_types=1);

namespace Report\Listener;

use Database\Model\Address as DatabaseAddressModel;
use Database\Model\Decision as DatabaseDecisionModel;
use Database\Model\MailingList as DatabaseMailingListModel;
use Database\Model\MailingListMember as DatabaseMailingListMemberModel;
use Database\Model\Meeting as DatabaseMeetingModel;
use Database\Model\Member as DatabaseMemberModel;
use Database\Model\SubDecision as DatabaseSubDecisionModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Report\Model\SubDecision as SubDecisionModel;
use Report\Model\SubDecision\Abrogation as ReportAbrogationModel;
use Report\Model\SubDecision\Board\Discharge as ReportBoardDischargeModel;
use Report\Model\SubDecision\Board\Installation as ReportBoardInstallationModel;
use Report\Model\SubDecision\Board\Release as ReportBoardReleaseModel;
use Report\Model\SubDecision\Discharge as ReportDischargeModel;
use Report\Model\SubDecision\Foundation as ReportFoundationModel;
use Report\Model\SubDecision\Installation as ReportInstallationModel;
use Report\Model\SubDecision\Key\Granting as ReportKeyGrantingModel;
use Report\Model\SubDecision\Key\Withdrawal as ReportKeyWithdrawalModel;
use Report\Service\Board as BoardService;
use Report\Service\Keyholder as KeyholderService;
use Report\Service\Meeting as MeetingService;
use Report\Service\Member as MemberService;
use Report\Service\Misc as MiscService;
use Report\Service\Organ as OrganService;

/**
 * Doctrine event listener intended to automatically update reportdb.
 */
class DatabaseUpdateListener
{
    protected static bool $isflushing = false;

    public function __construct(
        private readonly BoardService $boardService,
        private readonly KeyholderService $keyholderService,
        private readonly MeetingService $meetingService,
        private readonly MemberService $memberService,
        private readonly MiscService $miscService,
        private readonly OrganService $organService,
        private readonly EntityManager $emReport,
    ) {
    }

    protected static function safeFlush(callable $func): void
    {
        if (self::$isflushing) {
            return;
        }

        self::$isflushing = true;
        $func();
        self::$isflushing = false;
    }

    public function postPersist(LifecycleEventArgs $eventArgs): void
    {
        $this->postUpdate($eventArgs);
    }

    public function postUpdate(LifecycleEventArgs $eventArgs): void
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
                $this->processBoardMemberUpdates($subdecision);
                $this->processKeyholderUpdates($subdecision);
                $this->processOrganUpdates($subdecision);
                $this->emReport->persist($subdecision);
                break;

            case $entity instanceof DatabaseMailingListModel:
                $this->miscService->generateList($entity);
                break;

            case $entity instanceof DatabaseMailingListMemberModel:
                $this->miscService->generateListMembership($entity);
                break;

            default:
                return;
        }

        $em = $this->emReport;

        self::safeFlush(static function () use ($em): void {
            $em->flush();
        });
    }

    public function processOrganUpdates(SubDecisionModel $entity): void
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

    public function processKeyholderUpdates(SubDecisionModel $entity): void
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

    public function processBoardMemberUpdates(SubDecisionModel $entity): void
    {
        switch (true) {
            case $entity instanceof ReportBoardInstallationModel:
                $this->boardService->generateInstallation($entity);
                break;

            case $entity instanceof ReportBoardReleaseModel:
                $this->boardService->generateRelease($entity);
                break;

            case $entity instanceof ReportBoardDischargeModel:
                $this->boardService->generateDischarge($entity);
                break;
        }
    }
}
