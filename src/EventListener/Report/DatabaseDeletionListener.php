<?php

declare(strict_types=1);

namespace App\EventListener\Report;

use App\Entity\Database\Address;
use App\Entity\Database\Decision;
use App\Entity\Database\MailingList;
use App\Entity\Database\MailingListMember;
use App\Entity\Database\Meeting;
use App\Entity\Database\Member;
use App\Service\Report\MeetingService;
use App\Service\Report\MemberService;
use App\Service\Report\MiscService;
use App\Service\Report\ProjectionState;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Takes out of ReportDB what is taken out of the ledger.
 *
 * Before the removal rather than after it, because what has to go from the projection is worked out from the entity
 * while its relations are still readable.
 */
#[AsDoctrineListener(
    event: Events::preRemove,
    connection: 'default',
)]
final class DatabaseDeletionListener
{
    public function __construct(
        private readonly ProjectionState $state,
        private readonly MeetingService $meetingService,
        private readonly MemberService $memberService,
        private readonly MiscService $miscService,
        #[Autowire(service: 'doctrine.orm.report_entity_manager')]
        private readonly EntityManagerInterface $emReport,
    ) {
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        if (!$this->state->isEnabled()) {
            return;
        }

        $entity = $args->getObject();

        switch (true) {
            case $entity instanceof Address:
                $this->memberService->deleteAddress($entity);
                break;

            case $entity instanceof Member:
                // Cannot fail: a member that still has relations is not deleted, only stripped of their data.
                $this->memberService->deleteMember($entity);
                break;

            case $entity instanceof Meeting:
                // Nothing removes a meeting, and what removing one should do to the decisions projected from it has
                // never been decided, so it is refused rather than half-done.
                throw new RuntimeException('Deleting a meeting from ReportDB is not implemented');

            case $entity instanceof Decision:
                $this->meetingService->deleteDecision($entity);
                break;

            case $entity instanceof MailingList:
                $this->miscService->deleteList($entity);
                break;

            case $entity instanceof MailingListMember:
                $this->miscService->deleteListMembership($entity);
                break;

            default:
                return;
        }

        $this->emReport->flush();
    }
}
