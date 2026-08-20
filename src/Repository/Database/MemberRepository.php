<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Entity\Database\Address;
use App\Entity\Database\Enums\AddressTypes;
use App\Entity\Database\Enums\MemberFilter;
use App\Entity\Database\Enums\MembershipTypes;
use App\Entity\Database\Member;
use App\Entity\Database\Membership;
use App\Entity\Database\SubDecision\Annulment;
use App\Entity\Database\SubDecision\Board\Installation as BoardInstallation;
use App\Entity\Database\SubDecision\Discharge;
use App\Entity\Database\SubDecision\Financial\Budget;
use App\Entity\Database\SubDecision\Financial\Statement;
use App\Entity\Database\SubDecision\Installation;
use App\Repository\Database\SubDecision\FoundationRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;

use function addcslashes;
use function filter_var;
use function is_numeric;
use function mb_strtolower;
use function strtolower;
use function trim;

use const FILTER_VALIDATE_EMAIL;

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
     * See if we can find a member with the same email.
     */
    public function hasMemberWith(string $email): bool
    {
        $ret = $this->findByEmail($email);

        return null !== $ret;
    }

    public function findByEmail(string $email): ?Member
    {
        $qb = $this->createQueryBuilder('m');

        $qb->where('LOWER(m.email) = LOWER(:email)')
            ->setMaxResults(1);

        $qb->setParameter(
            ':email',
            $email,
        );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Search for a member.
     *
     * @return Member[]
     */
    public function search(
        string $query,
        bool $filtered = false,
    ): array {
        $qb = $this->createQueryBuilder('m');

        $qb->where("CONCAT(LOWER(m.firstName), ' ', LOWER(m.lastName)) LIKE :name")
            ->orWhere("CONCAT(LOWER(m.firstName), ' ', LOWER(m.middleName), ' ', LOWER(m.lastName)) LIKE :name")
            ->orWhere('m.studentNumber = :name')
            ->setMaxResults(32)
            ->orderBy(
                'm.lidnr',
                'DESC',
            )
            ->setFirstResult(0);

        if (
            filter_var(
                $query,
                FILTER_VALIDATE_EMAIL,
            )
        ) {
            $qb->orWhere('m.email LIKE :name');
        }

        $qb->setParameter(
            ':name',
            '%' . strtolower($query) . '%',
        );

        // also allow searching for membership number
        if (is_numeric($query)) {
            $qb->orWhere('m.lidnr = :nr');
            $qb->setParameter(
                ':nr',
                $query,
            );
        }

        if ($filtered) {
            $sq = self::getMembershipSubquery(
                $qb,
                includeGraduates: true,
                includeFutureMembers: true,
            );

            $qb->andWhere(
                $qb->expr()->in(
                    'm',
                    $sq->getDQL(),
                ),
            )
            ->andWhere('m.deleted = False')
            ->andWhere('m.hidden = False');
        }

        return $qb->getQuery()->getResult();
    }

    public function findMemberAddress(
        Member $member,
        AddressTypes $type,
    ): ?Address {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('a, m')
            ->from(
                Address::class,
                'a',
            )
            ->innerJoin(
                'a.member',
                'm',
            )
            ->where('m.lidnr = :lidnr')
            ->andWhere('a.type = :type')
            ->orderBy(
                'm.lidnr',
                'DESC',
            );

        $qb->setParameter(
            ':lidnr',
            $member,
        );
        $qb->setParameter(
            ':type',
            $type,
        );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find all non-hidden and non-deleted members.
     *
     * @return Member[]
     */
    public function findNormal(): array
    {
        $qb = $this->createQueryBuilder('m');

        $sq = self::getMembershipSubquery(
            $qb,
            includeGraduates: true,
            includeFutureMembers: true,
        );

        $qb->andWhere(
            $qb->expr()->in(
                'm',
                $sq->getDQL(),
            ),
        )
            ->andWhere('m.hidden = false')
            ->andWhere('m.deleted = false')
            ->setMaxResults(32)
            ->setFirstResult(0);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find a member (by lidnr), and calculate their organ memberships.
     *
     * Only installations that are neither discharged nor annulled (and whose discharge is not itself annulled) are
     * hydrated onto the member.
     */
    public function findWithInstallations(int $lidnr): ?Member
    {
        $qb = $this->createQueryBuilder('m');

        $qb->select('m, r, l')
            ->where('m.lidnr = :lidnr')
            ->leftJoin(
                'm.installations',
                'r',
            )
            ->leftJoin(
                'm.mailingListMemberships',
                'l',
            )
            ->andWhere('(r.function = \'Lid\' OR r.function = \'Inactief Lid\' OR r.function IS NULL)');

        // discharges
        $qbn = $this->getEntityManager()->createQueryBuilder();
        $qbn->select('d')
            ->from(
                Discharge::class,
                'd',
            )
            ->join(
                'd.installation',
                'x',
            )
            ->where('x.meeting_type = r.meeting_type')
            ->andWhere('x.meeting_number = r.meeting_number')
            ->andWhere('x.decision_point = r.decision_point')
            ->andWhere('x.decision_number = r.decision_number')
            ->andWhere('x.sequence = r.sequence');

        // annulled discharge decisions
        $qbnd = $this->getEntityManager()->createQueryBuilder();
        $qbnd->select('b')
            ->from(
                Annulment::class,
                'b',
            )
            ->join(
                'b.target',
                'z',
            )
            ->where('z.meeting_type = d.meeting_type')
            ->andWhere('z.meeting_number = d.meeting_number')
            ->andWhere('z.point = d.decision_point')
            ->andWhere('z.number = d.decision_number');

        $qbn->andWhere($qbn->expr()->not(
            $qbn->expr()->exists($qbnd->getDQL()),
        ));

        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qbn->getDQL()),
        ));

        // annulled installation decisions
        $qbd = $this->getEntityManager()->createQueryBuilder();
        $qbd->select('a')
            ->from(
                Annulment::class,
                'a',
            )
            ->join(
                'a.target',
                'y',
            )
            ->where('y.meeting_type = r.meeting_type')
            ->andWhere('y.meeting_number = r.meeting_number')
            ->andWhere('y.point = r.decision_point')
            ->andWhere('y.number = r.decision_number');

        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qbd->getDQL()),
        ));

        $qb->setParameter(
            ':lidnr',
            $lidnr,
        );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find a member (by lidnr).
     *
     * Do not calculate memberships.
     */
    public function findSimple(int $lidnr): ?Member
    {
        $qb = $this->createQueryBuilder('m');

        $qb->select('m, l')
            ->where('m.lidnr = :lidnr')
            ->leftJoin(
                'm.mailingListMemberships',
                'l',
            )
            ->orderBy(
                'm.lidnr',
                'DESC',
            );

        $qb->setParameter(
            ':lidnr',
            $lidnr,
        );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find all members whose membership expired on or before a date.
     *
     * @return Member[]
     */
    public function findExpired(DateTimeInterface $expiration): array
    {
        $qb = $this->createQueryBuilder('m');
        $qb->where('m.deleted = False');

        // Find all members who have a membership that was active at some point after a specific date
        $nemqb = $this->getEntityManager()->createQueryBuilder();
        $nemqb->select('IDENTITY(nem.member)')
            ->distinct()
            ->from(
                Membership::class,
                'nem',
            )
            ->where('nem.endDate > :expiration');

        // Exclude those members from the result
        $qb->andWhere(
            $qb->expr()->notIn(
                'm.lidnr',
                $nemqb->getDQL(),
            ),
        );

        $qb->setParameter(
            'expiration',
            $expiration,
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * Find members without an email address.
     *
     * @param int      $maxExpiredDays    Max number of days member can have been expired
     * @param int|null $expiresWithinDays Max number of days member can expire within
     *
     * @return Member[]
     */
    public function findAttentionWithoutEmail(
        int $maxExpiredDays = 90,
        ?int $expiresWithinDays = null,
    ): array {
        $today = $this->getToday();

        $qb = $this->createQueryBuilder('m');
        $qb->where('m.deleted = False')
            ->andWhere('m.email IS NULL');

        $sq = self::getDatedMembershipSubquery(
            $qb,
            endsAfter: $today->modify('-' . $maxExpiredDays . ' days'),
            endsBefore: null === $expiresWithinDays
                ? null
                : $today->modify('+' . $expiresWithinDays . ' days'),
        );

        $qb->andWhere(
            $qb->expr()->in(
                'm',
                $sq->getDQL(),
            ),
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * Find ordinary members without a student number.
     *
     * @param int      $maxExpiredDays    Max number of days member can have been expired
     * @param int|null $expiresWithinDays Max number of days member can expire within
     *
     * @return Member[]
     */
    public function findAttentionWithoutStudentNumber(
        int $maxExpiredDays = 90,
        ?int $expiresWithinDays = null,
    ): array {
        $today = $this->getToday();

        $qb = $this->createQueryBuilder('m');
        $qb->where('m.deleted = False')
            ->andWhere('m.studentNumber IS NULL');

        $sq = self::getDatedMembershipSubquery(
            $qb,
            endsAfter: $today->modify('-' . $maxExpiredDays . ' days'),
            endsBefore: null === $expiresWithinDays
                ? null
                : $today->modify('+' . $expiresWithinDays . ' days'),
            specificType: MembershipTypes::Ordinary,
        );

        $qb->andWhere(
            $qb->expr()->in(
                'm',
                $sq->getDQL(),
            ),
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * Find members who are expiring soon (active or nonactive) (of a specific type, if specified).
     *
     * @param bool     $inActiveIsActive  Also includes inactive organ members.
     * @param int      $maxExpiredDays    Max number of days member can have been expired
     * @param int|null $expiresWithinDays Max number of days member can expire within
     *
     * @return Member[]
     */
    public function findAttentionExpiring(
        bool $includeActive = true,
        bool $includeNonActive = true,
        bool $inActiveIsActive = false,
        ?MembershipTypes $specificType = null,
        int $maxExpiredDays = 90,
        ?int $expiresWithinDays = null,
    ): array {
        $qb = $this->createQueryBuilder('m');
        $qb->where('m.deleted = False');

        $today = $this->getToday();

        $sqM = self::getDatedMembershipSubquery(
            $qb,
            endsAfter: $today->modify('-' . $maxExpiredDays . ' days'),
            endsBefore: null === $expiresWithinDays
                ? null
                : $today->modify('+' . $expiresWithinDays . ' days'),
            specificType: $specificType,
        );

        $qb->andWhere(
            $qb->expr()->in(
                'm',
                $sqM->getDQL(),
            ),
        );

        if (
            !$includeActive
            && !$includeNonActive
        ) {
            return [];
        }

        if (
            !$includeActive
            || !$includeNonActive
        ) {
            // We use todays date to check if the member is active
            // It would be more accurate to check on the membership end date, but that would require more complex
            // queries and we don't expect any future decisions to be in the database.
            $sqA = FoundationRepository::getIsActiveWithinSubQuery(
                qb: $qb,
                activeBefore: $today,
                activeAfter: $today,
                inActiveIsActive: $inActiveIsActive,
            );

            if (!$includeActive) {
                $qb->andWhere(
                    $qb->expr()->notIn(
                        'm',
                        $sqA->getDQL(),
                    ),
                );
            } else {
                $qb->andWhere(
                    $qb->expr()->in(
                        'm',
                        $sqA->getDQL(),
                    ),
                );
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Check if we can fully remove a member.
     */
    public function canRemove(Member $member): bool
    {
        // check if the member is included in budgets
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('b')
            ->from(
                Budget::class,
                'b',
            )
            ->where('b.member = :member');
        $qb->setParameter(
            'member',
            $member,
        );

        $results = $qb->getQuery()->getResult();
        if (!empty($results)) {
            return false;
        }

        // check if the member is included in financial statements
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('b')
            ->from(
                Statement::class,
                'b',
            )
            ->where('b.member = :member');
        $qb->setParameter(
            'member',
            $member,
        );

        $results = $qb->getQuery()->getResult();

        if (!empty($results)) {
            return false;
        }

        // check if the member has been installed
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('i')
            ->from(
                Installation::class,
                'i',
            )
            ->where('i.member = :member');
        $qb->setParameter(
            'member',
            $member,
        );

        $results = $qb->getQuery()->getResult();

        if (!empty($results)) {
            return false;
        }

        // check if the member has been a board member
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('i')
            ->from(
                BoardInstallation::class,
                'i',
            )
            ->where('i.member = :member');
        $qb->setParameter(
            'member',
            $member,
        );

        $results = $qb->getQuery()->getResult();

        return empty($results);
    }

    /**
     * Get a list of members whose membership has not expired and who are not hidden.
     *
     * @return Member[]
     */
    public function getNonExpiredNonHiddenMembers(): array
    {
        $qb = $this->createQueryBuilder('m');
        $qb->where('m.hidden = False');

        $sq = self::getMembershipSubquery(
            $qb,
            includeGraduates: true,
            includeFutureMembers: false,
            includeExpired: false,
        );

        $qb->andWhere(
            $qb->expr()->in(
                'm',
                $sq->getDQL(),
            ),
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * Count the members who still have an active membership (and graduate status if `includeGraduates`), this means
     * that are not deleted and their `expiration` is later than now.
     *
     * If `isExpired`, this only counts expired members (and graduate status if `includeGraduates`).
     */
    public function countMembers(
        bool $includeGraduates = false,
        bool $includeFutureMembers = false,
        bool $includeExpired = false,
    ): int {
        $qb = $this->createQueryBuilder('m');
        $qb->select('COUNT(m.lidnr)')
            ->where('m.deleted = False');

        $sq = self::getMembershipSubquery(
            $qb,
            includeGraduates: $includeGraduates,
            includeFutureMembers: $includeFutureMembers,
            includeExpired: $includeExpired,
        );

        $qb->andWhere(
            $qb->expr()->in(
                'm',
                $sq->getDQL(),
            ),
        );

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * How many members hold a current membership of each type.
     *
     * Counts members rather than memberships, and someone who holds two current memberships of different types is
     * counted under both, so these do not add up to the number of members and are not rendered as a share of one.
     *
     * @return array<string, int> keyed by the value of `MembershipTypes`, in the order that enum declares them
     */
    public function countByMembershipType(): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(
            'ms.type AS type',
            'COUNT(DISTINCT ms.member) AS total',
        )
            ->from(
                Membership::class,
                'ms',
            )
            ->innerJoin(
                Member::class,
                'm',
                Join::WITH,
                'm = ms.member',
            )
            ->where('m.deleted = False')
            ->andWhere('ms.startDate <= CURRENT_TIMESTAMP()')
            ->andWhere('ms.endDate >= CURRENT_TIMESTAMP()')
            ->groupBy('ms.type');

        $counts = [];
        foreach ($qb->getQuery()->getResult() as $row) {
            $counts[$row['type']->value] = (int) $row['total'];
        }

        // A type nobody holds does not come back from a `GROUP BY`, and it is still a type: it is a zero, not a
        // missing row.
        $breakdown = [];
        foreach (MembershipTypes::cases() as $type) {
            $breakdown[$type->value] = $counts[$type->value] ?? 0;
        }

        return $breakdown;
    }

    /**
     * Returns a subquery containing IDENTIY(m) of all members that have a membership (with optional constraints).
     *
     * Builds a subquery, set parameters to the inputted QueryBuilder $qb, and returns the subquery.
     *
     * It is also possible to copy these parameters with getParameters() if there is more than 1 nesting going on.
     * However, you should foreach in that case (because setParameters() replaces all parameters, not adds them).
     * > foreach ($sq->getParameters() as $parameter) {
     * >     $qb->setParameter($parameter->getName(), $parameter->getValue());
     * > }
     */
    public static function getMembershipSubquery(
        QueryBuilder $qb,
        bool $includeGraduates = true,
        bool $includeFutureMembers = false,
        bool $includeExpired = false,
        ?MembershipTypes $specificType = null,
        string $membershipAlias = 'nemems',
        string $parameterPrefix = 'nems',
    ): QueryBuilder {
        $sq = $qb->getEntityManager()->createQueryBuilder();

        $sq->select('IDENTITY(' . $membershipAlias . '.member)')
            ->distinct()
            ->from(
                Membership::class,
                $membershipAlias,
            );

        if (!$includeGraduates) {
            $sq->andWhere($membershipAlias . '.type != :' . $parameterPrefix . 'graduate');
            $qb->setParameter(
                $parameterPrefix . 'graduate',
                MembershipTypes::Graduate,
            );
        }

        if (!$includeFutureMembers) {
            $sq->andWhere($membershipAlias . '.startDate <= CURRENT_TIMESTAMP()');
        }

        if (!$includeExpired) {
            $sq->andWhere($membershipAlias . '.endDate >= CURRENT_TIMESTAMP()');
        }

        if (
            MembershipTypes::Graduate === $specificType
            && !$includeGraduates
        ) {
            throw new InvalidArgumentException('Cannot specify graduate type if graduates are not included');
        }

        if (null !== $specificType) {
            $sq->andWhere($membershipAlias . '.type = :' . $parameterPrefix . 'specificType');
            $qb->setParameter(
                $parameterPrefix . 'specificType',
                $specificType,
            );
        }

        return $sq;
    }

    /**
     * Returns a subquery of all members that have a membership meeting
     * certain date or type conditions.
     *
     * Typically should not be used for a negative check (i.e. whose memberships are expired)
     * Better is to check whose memberships have not expired using this subquery and then
     * filter those in the main query. That will cover members without membership etc.
     */
    private static function getDatedMembershipSubquery(
        QueryBuilder $qb,
        ?DateTimeInterface $endsAfter = null,
        ?DateTimeInterface $endsBefore = null,
        ?MembershipTypes $specificType = null,
        bool $onlyLastMembership = true,
        string $membershipAlias = 'daMems',
        string $parameterPrefix = 'daMs',
    ): QueryBuilder {
        $sq = $qb->getEntityManager()->createQueryBuilder();

        // We take all unique memberships
        $sq->select('IDENTITY(' . $membershipAlias . '.member)')
            ->distinct()
            ->from(
                Membership::class,
                $membershipAlias,
            );

        // Of a given type, if specified
        if (null !== $specificType) {
            $sq->andWhere($membershipAlias . '.type = :' . $parameterPrefix . 'SpecificType');
            $qb->setParameter(
                $parameterPrefix . 'SpecificType',
                $specificType,
            );
        }

        // Which expire before a specific date, if specified
        if (null !== $endsBefore) {
            $sq->andWhere($membershipAlias . '.endDate <= :' . $parameterPrefix . 'EndsBefore');
            $qb->setParameter(
                $parameterPrefix . 'EndsBefore',
                $endsBefore,
            );
        }

        // Which expire after a specific date, if specified
        if (null !== $endsAfter) {
            $sq->andWhere($membershipAlias . '.endDate > :' . $parameterPrefix . 'EndsAfter');
            $qb->setParameter(
                $parameterPrefix . 'EndsAfter',
                $endsAfter,
            );
        }

        // And for which there does not exist a later membership (of any type, as long as it is after the current one)
        if ($onlyLastMembership) {
            $ssq = $qb->getEntityManager()->createQueryBuilder();
            $ssq->select('1') // we don't actually need to select any data, just check for existence
                ->from(
                    Membership::class,
                    $membershipAlias . 'Later',
                )
                ->where($membershipAlias . 'Later.member = ' . $membershipAlias . '.member')
                ->andWhere($membershipAlias . 'Later.startDate >= ' . $membershipAlias . '.endDate');

            $sq->andWhere($sq->expr()->not(
                $sq->expr()->exists($ssq->getDQL()),
            ));
        }

        return $sq;
    }

    private function getToday(): DateTimeImmutable
    {
        return new DateTimeImmutable()->setTime(
            0,
            0,
            0,
        );
    }

    /**
     * One page of the member overview, filtered the way the overview offers.
     *
     * @return Paginator<Member>
     */
    public function paginateForOverview(
        string $search,
        MemberFilter $filter,
        int $page,
        int $pageSize,
    ): Paginator {
        // The memberships come along: every row states the membership a member holds, and reading that off a lazy
        // collection is one query per row. `Paginator` pages the members first and fetches their memberships after,
        // so the join does not cut the page short.
        $qb = $this->createQueryBuilder('m')
            ->addSelect('memberships')
            ->leftJoin(
                'm.memberships',
                'memberships',
            )
            ->orderBy(
                'm.lidnr',
                'DESC',
            )
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize);

        $this->applyOverviewFilter(
            $qb,
            $filter,
        );
        $this->applyOverviewSearch(
            $qb,
            $search,
        );

        return new Paginator($qb->getQuery());
    }

    /**
     * How many records each filter would return, so the chips can say so before they are clicked.
     *
     * @return array<string, int>
     */
    public function countsForOverview(string $search): array
    {
        $counts = [];

        foreach (MemberFilter::cases() as $filter) {
            $qb = $this->createQueryBuilder('m')->select('COUNT(m.lidnr)');

            $this->applyOverviewFilter(
                $qb,
                $filter,
            );
            $this->applyOverviewSearch(
                $qb,
                $search,
            );

            $counts[$filter->value] = (int) $qb->getQuery()->getSingleScalarResult();
        }

        return $counts;
    }

    /**
     * A removed member is only ever reached through its own filter: they are kept so the decisions that mention them
     * stay readable, not so they turn up while looking for someone.
     */
    private function applyOverviewFilter(
        QueryBuilder $qb,
        MemberFilter $filter,
    ): void {
        if (MemberFilter::Removed === $filter) {
            $qb->andWhere('m.deleted = true');

            return;
        }

        $qb->andWhere('m.deleted = false');

        $membership = static function (bool $expired) use ($qb): void {
            $sq = self::getMembershipSubquery(
                $qb,
                includeGraduates: true,
                includeFutureMembers: true,
                includeExpired: $expired,
            );

            $qb->andWhere($qb->expr()->in('m', $sq->getDQL()));
        };

        match ($filter) {
            MemberFilter::Everyone => null,
            MemberFilter::Active => $membership(false),
            // Held a membership at some point but holds none now, which is the difference between the two subqueries.
            MemberFilter::Expired => (static function () use ($qb, $membership): void {
                $membership(true);

                $current = self::getMembershipSubquery(
                    $qb,
                    includeGraduates: true,
                    includeFutureMembers: true,
                    membershipAlias: 'curmems',
                    parameterPrefix: 'curms',
                );

                $qb->andWhere($qb->expr()->notIn('m', $current->getDQL()));
            })(),
            MemberFilter::MissingData => $qb->andWhere(
                $qb->expr()->orX(
                    'm.email IS NULL',
                    "m.email = ''",
                    $qb->expr()->andX(
                        'm.studentNumber IS NULL',
                        $qb->expr()->in(
                            'm',
                            self::getMembershipSubquery(
                                $qb,
                                includeGraduates: false,
                                specificType: MembershipTypes::Ordinary,
                                membershipAlias: 'ordmems',
                                parameterPrefix: 'ordms',
                            )->getDQL(),
                        ),
                    ),
                ),
            ),
        };
    }

    /**
     * Name, e-mail, member number or student number — whichever the secretary has to hand.
     */
    private function applyOverviewSearch(
        QueryBuilder $qb,
        string $search,
    ): void {
        $search = trim($search);

        if ('' === $search) {
            return;
        }

        $matches = $qb->expr()->orX(
            "CONCAT(LOWER(m.firstName), ' ', LOWER(m.lastName)) LIKE :needle",
            "CONCAT(LOWER(m.firstName), ' ', LOWER(m.middleName), ' ', LOWER(m.lastName)) LIKE :needle",
            'LOWER(m.email) LIKE :needle',
            'm.studentNumber LIKE :needle',
        );

        if (is_numeric($search)) {
            $matches->add('m.lidnr = :lidnr');
            $qb->setParameter(
                'lidnr',
                (int) $search,
            );
        }

        $qb->andWhere($matches)
            ->setParameter(
                'needle',
                '%' . mb_strtolower(addcslashes($search, '%_')) . '%',
            );
    }

    public function persist(Member $member): void
    {
        $this->getEntityManager()->persist($member);
        $this->getEntityManager()->flush();
    }

    /**
     * Persist several members in a single flush.
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

    public function remove(Member $member): void
    {
        $this->getEntityManager()->remove($member);
        $this->getEntityManager()->flush();
    }

    public function persistAddress(Address $address): void
    {
        $this->getEntityManager()->persist($address);
        $this->getEntityManager()->flush();
    }

    public function removeAddress(Address $address): void
    {
        $this->getEntityManager()->remove($address);
        $this->getEntityManager()->flush();
    }
}
