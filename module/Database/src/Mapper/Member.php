<?php

namespace Database\Mapper;

use Application\Model\Enums\AddressTypes;
use Database\Model\{
    Address as AddressModel,
    Member as MemberModel,
};
use Database\Model\SubDecision\{
    Budget as BudgetModel,
    Destroy as DestroyModel,
    Discharge as DischargeModel,
    Installation as InstallationModel,
};
use Doctrine\ORM\{
    EntityManager,
    EntityRepository,
};

class Member
{
    protected EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * See if we can find a member with the same email.
     */
    public function hasMemberWith(string $email): bool
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m')
            ->from(MemberModel::class, 'm')
            ->where("LOWER(m.email) = LOWER(:email)")
            ->setMaxResults(1);

        $qb->setParameter(':email', $email);

        $ret = $qb->getQuery()->getResult();

        return $ret !== null && count($ret) > 0;
    }

    /**
     * Search for a member.
     *
     * @return array<array-key, MemberModel>
     */
    public function search(string $query): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m')
            ->from(MemberModel::class, 'm')
            ->where("CONCAT(LOWER(m.firstName), ' ', LOWER(m.lastName)) LIKE :name")
            ->orWhere("CONCAT(LOWER(m.firstName), ' ', LOWER(m.middleName), ' ', LOWER(m.lastName)) LIKE :name")
            ->setMaxResults(32)
            ->orderBy('m.lidnr', 'DESC')
            ->setFirstResult(0);

        $qb->setParameter(':name', '%' . strtolower($query) . '%');

        // also allow searching for membership numbers
        if (is_numeric($query)) {
            $qb->orWhere("m.lidnr = :nr");
            $qb->setParameter(':nr', $query);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find a member address.
     */
    public function findMemberAddress(
        int $lidnr,
        AddressTypes $type,
    ): ?AddressModel {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a, m')
            ->from(AddressModel::class, 'a')
            ->innerJoin('a.member', 'm')
            ->where('m.lidnr = :lidnr')
            ->andWhere('a.type = :type')
            ->orderBy('m.lidnr', 'DESC');

        $qb->setParameter(':lidnr', $lidnr);
        $qb->setParameter(':type', $type);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find all members.
     *
     * @return array<array-key, MemberModel>
     */
    public function findAll(): array
    {
        return $this->getRepository()->findAll();
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
            ->leftJoin('m.lists', 'l')
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
            ->andWhere('x.number = r.number');

        // destroyed discharge decisions
        $qbnd = $this->em->createQueryBuilder();
        $qbnd->select('b')
            ->from(DestroyModel::class, 'b')
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

        // destroyed installation decisions
        $qbd = $this->em->createQueryBuilder();
        $qbd->select('a')
            ->from(DestroyModel::class, 'a')
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
            ->leftJoin('m.lists', 'l')
            ->orderBy('m.lidnr', 'DESC');


        $qb->setParameter(':lidnr', $lidnr);

        return $qb->getQuery()->getOneOrNullResult();
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
            ->where('b.author = :member');
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

        return true;
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
        return $this->em->getRepository('Database\Model\Member');
    }
}
