<?php

declare(strict_types=1);

namespace Database\Mapper;

use Application\Model\Enums\AddressTypes;
use Application\Model\Enums\MembershipTypes;
use Database\Model\Address as AddressModel;
use Database\Model\Member as MemberModel;
use Database\Model\SubDecision\Annulment as AnnulmentModel;
use Database\Model\SubDecision\Board\Installation as BoardInstallationModel;
use Database\Model\SubDecision\Discharge as DischargeModel;
use Database\Model\SubDecision\Financial\Budget as BudgetModel;
use Database\Model\SubDecision\Financial\Statement as StatementModel;
use Database\Model\SubDecision\Installation as InstallationModel;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use function count;
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

        return null !== $ret && count($ret) > 0;
    }

    /**
     * Find by email
     *
     * @return MemberModel[]
     */
    public function findByEmail(string $email): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m')
            ->from(MemberModel::class, 'm')
            ->where('LOWER(m.email) = LOWER(:email)')
            ->setMaxResults(1);

        $qb->setParameter(':email', $email);

        return $qb->getQuery()->getResult();
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
            $qb->andWhere('m.deleted = False AND m.hidden = False AND m.expiration > CURRENT_TIMESTAMP()');
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

        $qb->select('m')
            ->from(MemberModel::class, 'm')
            ->where('m.expiration >= CURRENT_TIMESTAMP()')
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
        $qb->where('m.expiration <= :expiration')
            ->andWhere('m.deleted = False')
            ->setParameter('expiration', $expiration);

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
        $qb->where('m.expiration > CURRENT_TIMESTAMP()')
            ->andWhere('m.hidden = False');

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
        bool $isExpired = false,
    ): int {
        $qb = $this->getRepository()->createQueryBuilder('m');
        $qb->select('COUNT(m.lidnr)')
            ->where('m.deleted = False');

        if (!$includeGraduates) {
            $qb->andWhere('m.type != :graduate')
                ->setParameter('graduate', MembershipTypes::Graduate);
        }

        if ($isExpired) {
            $qb->andWhere('m.expiration < CURRENT_TIMESTAMP()');
        } else {
            $qb->andWhere('m.expiration >= CURRENT_TIMESTAMP()');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countGraduates(bool $isExpired = false): int
    {
        $qb = $this->getRepository()->createQueryBuilder('m');
        $qb->select('COUNT(m.lidnr)')
            ->where('m.deleted = False')
            ->andWhere('m.type = :graduate');

        if ($isExpired) {
            $qb->andWhere('m.expiration < CURRENT_TIMESTAMP()');
        } else {
            $qb->andWhere('m.expiration >= CURRENT_TIMESTAMP()');
        }

        $qb->setParameter('graduate', MembershipTypes::Graduate);

        return (int) $qb->getQuery()->getSingleScalarResult();
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
