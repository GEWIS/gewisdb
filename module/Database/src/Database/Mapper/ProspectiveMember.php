<?php

namespace Database\Mapper;

use Database\Model\ProspectiveMember as ProspectiveMemberModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

class ProspectiveMember
{

    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;


    /**
     * Constructor
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * See if we can find a member with the same email.
     *
     * @param string $email
     *
     * @return boolean
     */
    public function hasMemberWith($email)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m')
            ->from('Database\Model\ProspectiveMember', 'm')
            ->where("m.email = :email")
            ->setMaxResults(1);

        $qb->setParameter(':email', $email);

        $ret = $qb->getQuery()->getResult();
        return $ret !== null && count($ret) > 0;
    }

    /**
     * Search for a member.
     *
     * @param string $query
     *
     * @return ProspectiveMemberModel
     */
    public function search($query)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m')
            ->from('Database\Model\ProspectiveMember', 'm')
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
     * Find all members.
     *
     * @return array of members
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Find a member (by lidnr).
     *
     * And calculate memberships.
     *
     * @param int $lidnr
     *
     * @return ProspectiveMemberModel
     */
    public function find($lidnr)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m, l')
            ->from('Database\Model\ProspectiveMember', 'm')
            ->where('m.lidnr = :lidnr')
            ->leftJoin('m.lists', 'l');

        $qb->setParameter(':lidnr', $lidnr);

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Persist a member model.
     *
     * @param ProspectiveMemberModel $member Member to persist.
     */
    public function persist(ProspectiveMemberModel $member)
    {
        $this->em->persist($member);
        $this->em->flush();
    }

    /**
     * Remove a member.
     *
     * @param ProspectiveMemberModel $member Member to remove
     */
    public function remove(ProspectiveMemberModel $member)
    {
        $this->em->remove($member);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Database\Model\ProspectiveMember');
    }

}
