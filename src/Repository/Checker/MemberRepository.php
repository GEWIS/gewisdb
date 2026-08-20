<?php

declare(strict_types=1);

namespace App\Repository\Checker;

use App\Entity\Application\AssociationYear;
use App\Entity\Database\Enums\MembershipTypes;
use App\Entity\Database\Member;
use App\Entity\Database\Membership;
use App\Entity\Database\RenewalLink;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Member>
 */
class MemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Member::class,
        );
    }

    /**
     * Get a list of members who are hidden or whose membership has expired.
     *
     * @return Member[]
     */
    public function getExpiredOrHiddenMembersWithAuthenticationKey(): array
    {
        $qb = $this->createQueryBuilder('m');

        $qb->leftJoin(
            'm.memberships',
            'mem',
        )
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
     * @return Member[]
     */
    public function getExpiringGraduates(
        ?DateTime $expiresBefore = null,
        ?int $limit = null,
    ): array {
        $qb = $this->createQueryBuilder('m');

        $qb->select('m, mem')
            ->leftJoin(
                'm.memberships',
                'mem',
            )
            ->where('mem.type = :graduate')
            ->andWhere('m.email IS NOT NULL')
            ->andWhere('m.hidden = false')
            ->andWhere('m.deleted = false')
            ->andWhere($qb->expr()->eq('mem.startDate', '(' . $this->lastMembershipQuery()->getDQL() . ')'))
            ->andWhere('mem.endDate <= :expiresBefore')
            ->setParameter(
                'graduate',
                MembershipTypes::Graduate,
            );

        $qbal = $this->getEntityManager()->createQueryBuilder();
        $qbal->select('rl')
            ->from(
                RenewalLink::class,
                'rl',
            )
            ->andWhere('rl.member = m')
            ->andWhere('rl.currentExpiration = mem.endDate');

        $qb->setParameter(
            'expiresBefore',
            $expiresBefore ?? AssociationYear::fromDate(new DateTime())->endsOn(),
        );

        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qbal->getDQL()),
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
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('MAX(lastMem.startDate)')
            ->from(
                Membership::class,
                'lastMem',
            )
            ->where('lastMem.member = ' . $memberAlias);

        return $qb;
    }

    /**
     * Persist several member models in a single flush.
     *
     * @param Member[] $members
     */
    public function persistAll(array $members): void
    {
        foreach ($members as $member) {
            $this->getEntityManager()->persist($member);
        }

        $this->getEntityManager()->flush();
    }
}
