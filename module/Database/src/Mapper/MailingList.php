<?php

namespace Database\Mapper;

use Database\Model\MailingList as ListModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

/**
 * Mailing list mapper.
 */
class MailingList
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
     * Persist a list.
     *
     * @param ListModel $list List to persist.
     */
    public function persist(ListModel $list)
    {
        $this->em->persist($list);
        $this->em->flush();
    }

    /**
     * Remove a list.
     *
     * @param ListModel $list
     */
    public function remove(ListModel $list)
    {
        $this->em->remove($list);
        $this->em->flush();
    }

    /**
     * Find all.
     *
     * @return array of ListModel's
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Find all mailing lists that are on the subscription form.
     *
     * @return array of ListModel's
     */
    public function findAllOnForm()
    {
        return $this->getRepository()->findBy(['onForm' => true]);
    }

    /**
     * Find all default
     *
     * @return array of ListModel's
     */
    public function findDefault()
    {
        return $this->getRepository()->findBy([
            'defaultSub' => true,
            'onForm' => false,
        ]);
    }

    /**
     * Find a list.
     *
     * @param string $name
     *
     * @return ListModel
     */
    public function find($name)
    {
        return $this->getRepository()->find($name);
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Database\Model\MailingList');
    }
}
