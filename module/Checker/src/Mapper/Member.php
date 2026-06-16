<?php

declare(strict_types=1);

namespace Checker\Mapper;

use Application\Model\Enums\MembershipTypes;
use Database\Model\Member as MemberModel;
use Database\Model\Membership as MembershipModel;
use Database\Model\RenewalLink as RenewalLinkModel;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

/**
 * Member mapper
 */
class Member
{
    /**
     * Constructor
     *
     * @param EntityManager $em Doctrine entity manager.
     */
    public function __construct(protected readonly EntityManager $em)
    {
    }

    /**
     * Get a list of members who are hidden or whose membership has expired.
     *
     * @return MemberModel[]
     */
    public function getExpiredOrHiddenMembersWithAuthenticationKey(): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('m')
            ->from(MemberModel::class, 'm')
            ->leftJoin('m.memberships', 'mem')
            ->where('m.authenticationKey IS NOT NULL')
            ->andWhere($qb->expr()->eq('mem.startDate', '(' . $this->lastMembershipQuery()->getDQL() . ')'))
            ->andWhere('mem.endDate <= CURRENT_TIMESTAMP() OR m.hidden = True');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all expiring graduates for which no renewal link exists
     * The check for hidden is required because hidden members may also expire but should not be emailed
     *
     * @param ?DateTime $expiresBefore Latest expiry date, end of current association year if null
     *
     * @return MemberModel[]
     */
    public function getExpiringGraduates(
        ?DateTime $expiresBefore = null,
        ?int $limit = null,
    ): array {
        $qb = $this->em->createQueryBuilder();
        $qb->select('m, mem')
            ->from(MemberModel::class, 'm')
            ->leftJoin('m.memberships', 'mem')
            ->where('mem.type = :graduate')
            ->andWhere('m.email IS NOT NULL')
            ->andWhere('m.hidden = false')
            ->andWhere('m.deleted = false')
            ->andWhere($qb->expr()->eq('mem.startDate', '(' . $this->lastMembershipQuery()->getDQL() . ')'))
            ->andWhere('mem.endDate <= :expiresBefore')
            ->setParameter('graduate', MembershipTypes::Graduate);

        $qbal = $this->em->createQueryBuilder();
        $qbal->select('rl')
            ->from(RenewalLinkModel::class, 'rl')
            ->andWhere('rl.member = m')
            ->andWhere('rl.currentExpiration = mem.endDate');

        $qb->setParameter('expiresBefore', $expiresBefore ?? $this->getEndOfCurrentAssociationYear());

        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qbal->getDql()),
        ));

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * This helper query is used for multiple queries to get the LAST membership of a member.
     * This is not necessarily the current membership.
     * We use the startDate because it is guaranteed to be unique in combination with member.lidnr.
     */
    private function lastMembershipQuery(string $memberAlias = 'm'): QueryBuilder
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('MAX(lastMem.startDate)')
            ->from(MembershipModel::class, 'lastMem')
            ->where('lastMem.member = ' . $memberAlias);

        return $qb;
    }

    private function getEndOfCurrentAssociationYear(): DateTime
    {
        $end = new DateTime();
        $end->setTime(0, 0);

        if ($end->format('m') >= 7) {
            $year = (int) $end->format('Y') + 1;
        } else {
            $year = (int) $end->format('Y');
        }

        $end->setDate($year, 7, 1);

        return $end;
    }

    /**
     * Persist a member model.
     *
     * @param MemberModel $member Member to persist.
     */
    public function persist(MemberModel $member): void
    {
        $this->em->persist($member);
        $this->em->flush();
    }
}
