<?php

namespace Report\Listener;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Report\Service\Meeting as MeetingService;
use Report\Service\Member as MemberService;

/**
 * Doctrine event listener intended to automatically update reportdb.
 */
class DatabaseDeletionListener
{
    /** @var MeetingService $meetingService */
    private $meetingService;

    /** @var MemberService $memberService */
    private $memberService;

    /** @var EntityManager */
    private $emReport;

    public function __construct(
        MeetingService $meetingService,
        MemberService $memberService,
        EntityManager $emReport
    ) {
        $this->meetingService = $meetingService;
        $this->memberService = $memberService;
        $this->emReport = $emReport;
    }

    public function preRemove($eventArgs)
    {
        $entity = $eventArgs->getEntity();
        switch (true) {
            case $entity instanceof \Database\Model\Address:
                $this->memberService->deleteAddress($entity);
                break;
            case $entity instanceof \Database\Model\Member:
                try {
                    $this->memberService->deleteMember($entity);
                } catch (ForeignKeyConstraintViolationException $e) {
                    // Member has relations, so we'll just leave it in reportdb
                }
                $this->memberService->deleteMember($entity);
                break;

            case $entity instanceof \Database\Model\Meeting:
                throw new \Exception('reportdb deletion of meetings not implemented');
                break;

            case $entity instanceof \Database\Model\Decision:
                $this->meetingService->deleteDecision($entity);
                break;
        }

        $this->emReport->flush();
    }
}
