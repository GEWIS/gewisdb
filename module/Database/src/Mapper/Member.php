<?php

declare(strict_types=1);

namespace Database\Mapper;

use Application\Model\Enums\AddressTypes;
use Application\Model\Enums\MembershipTypes;
use Database\Model\Address as AddressModel;
use Database\Model\Member as MemberModel;
use Database\Model\Membership as MembershipModel;
use Database\Model\SubDecision\Annulment as AnnulmentModel;
use Database\Model\SubDecision\Board\Installation as BoardInstallationModel;
use Database\Model\SubDecision\Discharge as DischargeModel;
use Database\Model\SubDecision\Financial\Budget as BudgetModel;
use Database\Model\SubDecision\Financial\Statement as StatementModel;
use Database\Model\SubDecision\Installation as InstallationModel;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;

use function filter_var;
use function is_numeric;
use function strtolower;

use const FILTER_VALIDATE_EMAIL;

class Member
{
    public function __construct(protected readonly EntityManager $em)
    {
    }

    /**
     * See if we can find a member with the same email.
     */
    public function hasMemberWith(string $email): bool
    {
        $ret = $this->findByEmail($email);

        return null !== $ret;
    }

    /**
     * Find by email
     */
    public function findByEmail(string $email): ?MemberModel
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m')
            ->from(MemberModel::class, 'm')
            ->where('LOWER(m.email) = LOWER(:email)')
            ->setMaxResults(1);

        $qb->setParameter(':email', $email);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Search for a member.
     *
     * @return MemberModel[]
     */
    public function search(
        string $query,
        bool $filtered = false,
    ): array {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m')
            ->from(MemberModel::class, 'm')
            ->where("CONCAT(LOWER(m.firstName), ' ', LOWER(m.lastName)) LIKE :name")
            ->orWhere("CONCAT(LOWER(m.firstName), ' ', LOWER(m.middleName), ' ', LOWER(m.lastName)) LIKE :name")
            ->orWhere('m.tueUsername = :name')
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

    /**
     * Find a member address.
     */
    public function findMemberAddress(
        MemberModel $member,
        AddressTypes $type,
    ): ?AddressModel {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a, m')
            ->from(AddressModel::class, 'a')
            ->innerJoin('a.member', 'm')
            ->where('m.lidnr = :lidnr')
            ->andWhere('a.type = :type')
            ->orderBy('m.lidnr', 'DESC');

        $qb->setParameter(':lidnr', $member);
        $qb->setParameter(':type', $type);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find all members.
     *
     * @return MemberModel[]
     */
    public function findAll(): array
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Find all non-hidden and non-deleted members.
     *
     * @return MemberModel[]
     */
    public function findNormal(): array
    {
        $qb = $this->em->createQueryBuilder();

        $sq = self::getMembershipSubquery(
            $qb,
            includeGraduates: true,
            includeFutureMembers: true,
        );

        $qb->select('m')
            ->from(MemberModel::class, 'm')
            ->andWhere(
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
     * Find a member (by lidnr).
     *
     * And calculate memberships.
     */
    public function find(int $lidnr): ?MemberModel
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m, r, l')
            ->from(MemberModel::class, 'm')
            ->where('m.lidnr = :lidnr')
            ->leftJoin('m.installations', 'r')
            ->leftJoin('m.mailingListMemberships', 'l')
            ->andWhere('(r.function = \'Lid\' OR r.function = \'Inactief Lid\' OR r.function IS NULL)');

        // discharges
        $qbn = $this->em->createQueryBuilder();
        $qbn->select('d')
            ->from(DischargeModel::class, 'd')
            ->join('d.installation', 'x')
            ->where('x.meeting_type = r.meeting_type')
            ->andWhere('x.meeting_number = r.meeting_number')
            ->andWhere('x.decision_point = r.decision_point')
            ->andWhere('x.decision_number = r.decision_number')
            ->andWhere('x.sequence = r.sequence');

        // annulled discharge decisions
        $qbnd = $this->em->createQueryBuilder();
        $qbnd->select('b')
            ->from(AnnulmentModel::class, 'b')
            ->join('b.target', 'z')
            ->where('z.meeting_type = d.meeting_type')
            ->andWhere('z.meeting_number = d.meeting_number')
            ->andWhere('z.point = d.decision_point')
            ->andWhere('z.number = d.decision_number');

        $qbn->andWhere($qbn->expr()->not(
            $qbn->expr()->exists($qbnd->getDql()),
        ));

        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qbn->getDql()),
        ));

        // annulled installation decisions
        $qbd = $this->em->createQueryBuilder();
        $qbd->select('a')
            ->from(AnnulmentModel::class, 'a')
            ->join('a.target', 'y')
            ->where('y.meeting_type = r.meeting_type')
            ->andWhere('y.meeting_number = r.meeting_number')
            ->andWhere('y.point = r.decision_point')
            ->andWhere('y.number = r.decision_number');

        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qbd->getDql()),
        ));

        $qb->setParameter(':lidnr', $lidnr);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find a member (by lidnr).
     *
     * Do not calculate memberships.
     */
    public function findSimple(int $lidnr): ?MemberModel
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m, l')
            ->from('Database\Model\Member', 'm')
            ->where('m.lidnr = :lidnr')
            ->leftJoin('m.mailingListMemberships', 'l')
            ->orderBy('m.lidnr', 'DESC');

        $qb->setParameter(':lidnr', $lidnr);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find all members whose membership expired on or before a date.
     *
     * @return MemberModel[]
     */
    public function findExpired(DateTime $expiration): array
    {
        $qb = $this->getRepository()->createQueryBuilder('m');
        $qb->where('m.deleted = False');

        // Find all members who have a membership that was active at some point after a specific date
        $nemqb = $this->em->createQueryBuilder();
        $nemqb->select('IDENTITY(nem.member)')
            ->distinct()
            ->from(MembershipModel::class, 'nem')
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
     * Check if we can fully remove a member.
     */
    public function canRemove(MemberModel $member): bool
    {
        // check if the member is included in budgets
        $qb = $this->em->createQueryBuilder();

        $qb->select('b')
            ->from(BudgetModel::class, 'b')
            ->where('b.member = :member');
        $qb->setParameter('member', $member);

        $results = $qb->getQuery()->getResult();
        if (!empty($results)) {
            return false;
        }

        // check if the member is included in financial statements
        $qb = $this->em->createQueryBuilder();

        $qb->select('b')
            ->from(StatementModel::class, 'b')
            ->where('b.member = :member');
        $qb->setParameter('member', $member);

        $results = $qb->getQuery()->getResult();

        if (!empty($results)) {
            return false;
        }

        // check if the member has been installed
        $qb = $this->em->createQueryBuilder();

        $qb->select('i')
            ->from(InstallationModel::class, 'i')
            ->where('i.member = :member');
        $qb->setParameter('member', $member);

        $results = $qb->getQuery()->getResult();

        if (!empty($results)) {
            return false;
        }

        // check if the member has been a board member
        $qb = $this->em->createQueryBuilder();

        $qb->select('i')
            ->from(BoardInstallationModel::class, 'i')
            ->where('i.member = :member');
        $qb->setParameter('member', $member);

        $results = $qb->getQuery()->getResult();

        return empty($results);
    }

    /**
     * Get a list of members whose membership has not expired and who are not hidden.
     *
     * @return MemberModel[]
     */
    public function getNonExpiredNonHiddenMembers(): array
    {
        $qb = $this->getRepository()->createQueryBuilder('m');
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
        $qb = $this->getRepository()->createQueryBuilder('m');
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
            ->from(MembershipModel::class, $membershipAlias);

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
     * Persist a member model.
     */
    public function persist(MemberModel $member): void
    {
        $this->em->persist($member);
        $this->em->flush();
    }

    /**
     * Remove a member.
     */
    public function remove(MemberModel $member): void
    {
        $this->em->remove($member);
        $this->em->flush();
    }

    /**
     * Persist an address.
     */
    public function persistAddress(AddressModel $address): void
    {
        $this->em->persist($address);
        $this->em->flush();
    }

    /**
     * Remove an address.
     */
    public function removeAddress(AddressModel $address): void
    {
        $this->em->remove($address);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     */
    public function getRepository(): EntityRepository
    {
        return $this->em->getRepository(MemberModel::class);
    }
}
