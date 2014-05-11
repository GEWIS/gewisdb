<?php

namespace Database\Mapper;

use Database\Model\Event as EventModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

/**
 * Event mapper
 */
class Event
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
     * Persist an event.
     *
     * @param EventModel $event Event to persist.
     */
    public function persist(EventModel $event)
    {
        $this->em->persist($event);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Database\Model\Event');
    }

}
