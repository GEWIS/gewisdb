<?php

declare(strict_types=1);

namespace App\EventListener\Report;

use App\Entity\Database\Address;
use App\Entity\Database\Decision;
use App\Entity\Database\MailingList;
use App\Entity\Database\MailingListMember;
use App\Entity\Database\Meeting;
use App\Entity\Database\Member;
use App\Entity\Database\SubDecision;
use App\Service\Report\MeetingService;
use App\Service\Report\MemberService;
use App\Service\Report\MiscService;
use App\Service\Report\SubDecisionService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Keeps ReportDB level with the ledger as the ledger is written.
 *
 * ReportDB is a projection, not a copy taken on a schedule: writing a member, a decision or a list here writes the
 * corresponding row there in the same request. `report:generate:full` rebuilds the whole projection from scratch and
 * is for a first fill or a repair, not for keeping it current.
 */
#[AsDoctrineListener(event: Events::postPersist, connection: 'default')]
#[AsDoctrineListener(event: Events::postUpdate, connection: 'default')]
final class DatabaseUpdateListener
{
    /**
     * Generating into ReportDB flushes it, and that flush reaches this listener again through the entities it just
     * wrote. The flag stops the recursion; it is static because Doctrine may hand out more than one instance.
     */
    private static bool $flushing = false;

    public function __construct(
        private readonly MeetingService $meetingService,
        private readonly MemberService $memberService,
        private readonly MiscService $miscService,
        private readonly SubDecisionService $subDecisionService,
        #[Autowire(service: 'doctrine.orm.report_entity_manager')]
        private readonly EntityManagerInterface $emReport,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->project($args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->project($args->getObject());
    }

    private function project(object $entity): void
    {
        switch (true) {
            case $entity instanceof Address:
                $this->memberService->generateAddress($entity);
                break;

            case $entity instanceof Member:
                $this->memberService->generateMember($entity);
                break;

            case $entity instanceof Meeting:
                $this->meetingService->generateMeeting($entity);
                break;

            case $entity instanceof Decision:
                $this->meetingService->generateDecision($entity);
                break;

            case $entity instanceof SubDecision:
                $subDecision = $this->meetingService->generateSubDecision($entity);
                $this->subDecisionService->generateRelated($subDecision);
                $this->emReport->persist($subDecision);
                break;

            case $entity instanceof MailingList:
                $this->miscService->generateList($entity);
                break;

            case $entity instanceof MailingListMember:
                // A subscription that is on its way out is removed from the projection rather than written to it.
                if ($entity->isToBeDeleted()) {
                    $this->miscService->deleteListMembership($entity);
                } else {
                    $this->miscService->generateListMembership($entity);
                }

                break;

            default:
                return;
        }

        if (self::$flushing) {
            return;
        }

        self::$flushing = true;

        try {
            $this->emReport->flush();
        } finally {
            self::$flushing = false;
        }
    }
}
