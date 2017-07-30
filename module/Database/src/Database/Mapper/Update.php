<?php

namespace Database\Mapper;


use Database\Model\MemberUpdate as MemberUpdateModel;
use Doctrine\ORM\EntityManager;

class Update
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
     * Find a member update by the lidnr
     *
     * @param $lidnr
     * @return null|object
     */
    public function findMemberUpdate($lidnr)
    {
        return $this->em->getRepository('Database\Model\MemberUpdate')->findOneBy(['lidnr' => $lidnr]);
    }
    /**
     * Persist an update model.
     *
     * @param Model $update update to persist.
     */
    public function persist($update)
    {
        $this->em->persist($update);
        $this->em->flush();
    }

    /**
     * Remove an update model.
     *
     * @param Model $update update to remove.
     */
    public function remove($update)
    {
        $this->em->remove($update);
        $this->em->flush();
    }

}
