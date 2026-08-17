<?php

declare(strict_types=1);

namespace App\Repository\Member;

use App\Entity\Decision\SubDecision\Annulment;
use App\Entity\Decision\SubDecision\Board\Installation as BoardInstallation;
use App\Entity\Decision\SubDecision\Discharge;
use App\Entity\Decision\SubDecision\Financial\Budget;
use App\Entity\Decision\SubDecision\Financial\Statement;
use App\Entity\Decision\SubDecision\Installation;
use App\Entity\Member\Address;
use App\Entity\Member\Enums\AddressTypes;
use App\Entity\Member\Enums\MembershipTypes;
use App\Entity\Member\Member;
use App\Entity\Member\Membership;
use App\Repository\Decision\OrganRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;

use function filter_var;
use function is_numeric;
use function strtolower;

use const FILTER_VALIDATE_EMAIL;

/**
 * @extends ServiceEntityRepository<Member>
 */
class MemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Member::class);
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

        $qb->setParameter(':email', $email);

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
            ->orderBy('m.lidnr', 'DESC')
            ->setFirstResult(0);

        if (filter_var($query, FILTER_VALIDATE_EMAIL)) {
            $qb->orWhere('m.email LIKE :name');
        }

        $qb->setParameter(':name', '%' . strtolower($query) . '%');

        // also allow searching for membership number
        if (is_numeric($query)) {
            $qb->orWhere('m.lidnr = :nr');
            $qb->setParameter(':nr', $query);
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
            ->from(Address::class, 'a')
            ->innerJoin('a.member', 'm')
            ->where('m.lidnr = :lidnr')
            ->andWhere('a.type = :type')
            ->orderBy('m.lidnr', 'DESC');

        $qb->setParameter(':lidnr', $member);
        $qb->setParameter(':type', $type);

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
            ->leftJoin('m.installations', 'r')
            ->leftJoin('m.mailingListMemberships', 'l')
            ->andWhere('(r.function = \'Lid\' OR r.function = \'Inactief Lid\' OR r.function IS NULL)');

        // discharges
        $qbn = $this->getEntityManager()->createQueryBuilder();
        $qbn->select('d')
            ->from(Discharge::class, 'd')
            ->join('d.installation', 'x')
            ->where('x.meeting_type = r.meeting_type')
            ->andWhere('x.meeting_number = r.meeting_number')
            ->andWhere('x.decision_point = r.decision_point')
            ->andWhere('x.decision_number = r.decision_number')
            ->andWhere('x.sequence = r.sequence');

        // annulled discharge decisions
        $qbnd = $this->getEntityManager()->createQueryBuilder();
        $qbnd->select('b')
            ->from(Annulment::class, 'b')
            ->join('b.target', 'z')
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
            ->from(Annulment::class, 'a')
            ->join('a.target', 'y')
            ->where('y.meeting_type = r.meeting_type')
            ->andWhere('y.meeting_number = r.meeting_number')
            ->andWhere('y.point = r.decision_point')
            ->andWhere('y.number = r.decision_number');

        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qbd->getDQL()),
        ));

        $qb->setParameter(':lidnr', $lidnr);

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
            ->leftJoin('m.mailingListMemberships', 'l')
            ->orderBy('m.lidnr', 'DESC');

        $qb->setParameter(':lidnr', $lidnr);

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
            ->from(Membership::class, 'nem')
            ->where('nem.endDate > :expiration');

        // Exclude those members from the result
        $qb->andWhere(
            $qb->expr()->notIn(
                'm.lidnr',
                $nemqb->getDQL(),
            ),
        );

        $qb->setParameter('expiration', $expiration);

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

        if (!$includeActive && !$includeNonActive) {
            return [];
        }

        if (!$includeActive || !$includeNonActive) {
            // We use todays date to check if the member is active
            // It would be more accurate to check on the membership end date, but that would require more complex
            // queries and we don't expect any future decisions to be in the database.
            $sqA = OrganRepository::getIsActiveWithinSubQuery(
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
            ->from(Budget::class, 'b')
            ->where('b.member = :member');
        $qb->setParameter('member', $member);

        $results = $qb->getQuery()->getResult();
        if (!empty($results)) {
            return false;
        }

        // check if the member is included in financial statements
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('b')
            ->from(Statement::class, 'b')
            ->where('b.member = :member');
        $qb->setParameter('member', $member);

        $results = $qb->getQuery()->getResult();

        if (!empty($results)) {
            return false;
        }

        // check if the member has been installed
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('i')
            ->from(Installation::class, 'i')
            ->where('i.member = :member');
        $qb->setParameter('member', $member);

        $results = $qb->getQuery()->getResult();

        if (!empty($results)) {
            return false;
        }

        // check if the member has been a board member
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('i')
            ->from(BoardInstallation::class, 'i')
            ->where('i.member = :member');
        $qb->setParameter('member', $member);

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
            ->from(Membership::class, $membershipAlias);

        if (!$includeGraduates) {
            $sq->andWhere($membershipAlias . '.type != :' . $parameterPrefix . 'graduate');
            $qb->setParameter($parameterPrefix . 'graduate', MembershipTypes::Graduate);
        }

        if (!$includeFutureMembers) {
            $sq->andWhere($membershipAlias . '.startDate <= CURRENT_TIMESTAMP()');
        }

        if (!$includeExpired) {
            $sq->andWhere($membershipAlias . '.endDate >= CURRENT_TIMESTAMP()');
        }

        if (MembershipTypes::Graduate === $specificType && !$includeGraduates) {
            throw new InvalidArgumentException('Cannot specify graduate type if graduates are not included');
        }

        if (null !== $specificType) {
            $sq->andWhere($membershipAlias . '.type = :' . $parameterPrefix . 'specificType');
            $qb->setParameter($parameterPrefix . 'specificType', $specificType);
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
            ->from(Membership::class, $membershipAlias);

        // Of a given type, if specified
        if (null !== $specificType) {
            $sq->andWhere($membershipAlias . '.type = :' . $parameterPrefix . 'SpecificType');
            $qb->setParameter($parameterPrefix . 'SpecificType', $specificType);
        }

        // Which expire before a specific date, if specified
        if (null !== $endsBefore) {
            $sq->andWhere($membershipAlias . '.endDate <= :' . $parameterPrefix . 'EndsBefore');
            $qb->setParameter($parameterPrefix . 'EndsBefore', $endsBefore);
        }

        // Which expire after a specific date, if specified
        if (null !== $endsAfter) {
            $sq->andWhere($membershipAlias . '.endDate > :' . $parameterPrefix . 'EndsAfter');
            $qb->setParameter($parameterPrefix . 'EndsAfter', $endsAfter);
        }

        // And for which there does not exist a later membership (of any type, as long as it is after the current one)
        if ($onlyLastMembership) {
            $ssq = $qb->getEntityManager()->createQueryBuilder();
            $ssq->select('1') // we don't actually need to select any data, just check for existence
                ->from(Membership::class, $membershipAlias . 'Later')
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
        return (new DateTimeImmutable())->setTime(0, 0, 0);
    }

    public function persist(Member $member): void
    {
        $this->getEntityManager()->persist($member);
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
