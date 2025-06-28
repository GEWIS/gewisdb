<?php

declare(strict_types=1);

namespace Report\Listener;

use Database\Model\Address as DatabaseAddressModel;
use Database\Model\Decision as DatabaseDecisionModel;
use Database\Model\MailingList as DatabaseMailingListModel;
use Database\Model\MailingListMember as DatabaseMailingListMemberModel;
use Database\Model\Meeting as DatabaseMeetingModel;
use Database\Model\Member as DatabaseMemberModel;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Exception;
use Report\Service\Meeting as MeetingService;
use Report\Service\Member as MemberService;
use Report\Service\Misc as MiscService;

/**
 * Doctrine event listener intended to automatically update reportdb.
 */
class DatabaseDeletionListener
{
    public function __construct(
        private readonly MeetingService $meetingService,
        private readonly MemberService $memberService,
        private readonly MiscService $miscService,
        private readonly EntityManager $emReport,
    ) {
    }

    public function preRemove(LifecycleEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getEntity();
        switch (true) {
            case $entity instanceof DatabaseAddressModel:
                $this->memberService->deleteAddress($entity);

                break;
            case $entity instanceof DatabaseMemberModel:
                try {
                    $this->memberService->deleteMember($entity);
                } catch (ForeignKeyConstraintViolationException) {
                    // Member has relations, so we'll just leave it in reportdb
                }

                $this->memberService->deleteMember($entity);

                break;
            case $entity instanceof DatabaseMeetingModel:
                throw new Exception('reportdb deletion of meetings not implemented');

            case $entity instanceof DatabaseDecisionModel:
                $this->meetingService->deleteDecision($entity);

                break;

            case $entity instanceof DatabaseMailingListModel:
                $this->miscService->deleteList($entity);

                break;

            case $entity instanceof DatabaseMailingListMemberModel:
                $this->miscService->deleteListMembership($entity);

                break;
        }

        $this->emReport->flush();
    }
}
