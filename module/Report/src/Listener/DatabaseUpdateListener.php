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
use Report\Service\Meeting as MeetingService;
use Report\Service\Member as MemberService;
use Report\Service\Misc as MiscService;
use Report\Service\SubDecision as SubDecisionService;

/**
 * Doctrine event listener intended to automatically update reportdb.
 */
class DatabaseUpdateListener
{
    protected static bool $isflushing = false;

    public function __construct(
        private readonly MeetingService $meetingService,
        private readonly MemberService $memberService,
        private readonly MiscService $miscService,
        private readonly SubDecisionService $subDecisionService,
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
                $this->subDecisionService->generateRelated($subdecision);
                $this->emReport->persist($subdecision);
                break;

            case $entity instanceof DatabaseMailingListModel:
                $this->miscService->generateList($entity);
                break;

            case $entity instanceof DatabaseMailingListMemberModel:
                if ($entity->isToBeDeleted()) {
                    $this->miscService->deleteListMembership($entity);
                } else {
                    $this->miscService->generateListMembership($entity);
                }

                break;

            default:
                return;
        }

        $em = $this->emReport;

        self::safeFlush(static function () use ($em): void {
            $em->flush();
        });
    }
}
