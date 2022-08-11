<?php

namespace Report\Listener;

use Database\Model\{
    Address as DatabaseAddressModel,
    Decision as DatabaseDecisionModel,
    Meeting as DatabaseMeetingModel,
    Member as DatabaseMemberModel,
};
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Exception;
use Report\Service\{
    Meeting as MeetingService,
    Member as MemberService,
};

/**
 * Doctrine event listener intended to automatically update reportdb.
 */
class DatabaseDeletionListener
{
    public function __construct(
        private readonly MeetingService $meetingService,
        private readonly MemberService $memberService,
        private readonly EntityManager $emReport,
    ) {
    }

    public function preRemove(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        switch (true) {
            case $entity instanceof DatabaseAddressModel:
                $this->memberService->deleteAddress($entity);
                break;
            case $entity instanceof DatabaseMemberModel:
                try {
                    $this->memberService->deleteMember($entity);
                } catch (ForeignKeyConstraintViolationException $e) {
                    // Member has relations, so we'll just leave it in reportdb
                }
                $this->memberService->deleteMember($entity);
                break;

            case $entity instanceof DatabaseMeetingModel:
                throw new Exception('reportdb deletion of meetings not implemented');
                break;

            case $entity instanceof DatabaseDecisionModel:
                $this->meetingService->deleteDecision($entity);
                break;
        }

        $this->emReport->flush();
    }
}
