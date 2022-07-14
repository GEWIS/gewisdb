<?php

namespace Database\Service;

use Database\Mapper\Event as EventMapper;
use Laminas\EventManager\SharedEventManager;
use Laminas\EventManager\Event as EmEvent;
use Database\Model\Event as EventModel;

/**
 * TODO: Does not work.
 */
class Event
{
    /** @var EventMapper $eventMapper */
    private $eventMapper;

    /** @var array $services */
    private $services;

    /**
     * @param EventMapper $eventMapper
     * @param array $services
     */
    public function __construct(
        EventMapper $eventMapper,
        array $services
    ) {
        $this->eventMapper = $eventMapper;
        $this->services = $services;
    }

    /**
     * Register the logging event.
     */
    public function register()
    {
        // TODO: This shared event manager should actually be shared.
        $em = new SharedEventManager();

        foreach ($this->services as $service) {
            $em->attach($service, '*', array($this, 'log'));
        }
    }

    /**
     * Log an event.
     *
     * @param EmEvent $e EmEvent to be logged.
     */
    public function log(EmEvent $e)
    {
        $event = new EventModel();

        $event->setName($e->getName());
        $event->setContext(get_class($e->getTarget()));
        $event->setParameters(serialize($e->getParams()));

        $this->eventMapper->persist($event);
    }
}
