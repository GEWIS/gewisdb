<?php

namespace Database\Service;

use Application\Service\AbstractService;
use Zend\EventManager\StaticEventManager;
use Zend\EventManager\Event as EmEvent;
use Database\Model\Event as EventModel;

class Event extends AbstractService
{
    /**
     * Services that are to be logged.
     */
    protected $services = [
        'Database\Service\Member',
        'Database\Service\Meeting'
    ];

    /**
     * Register the logging event.
     */
    public function register()
    {
        $em = StaticEventManager::getInstance();
        $em->attach($this->services, '*', [$this, 'log']);
    }

    /**
     * Log an event.
     *
     * @param Event $e EmEvent to be logged.
     */
    public function log(EmEvent $e)
    {
        $event = new EventModel();

        $event->setName($e->getName());
        $event->setContext(get_class($e->getTarget()));
        $event->setParameters(serialize($e->getParams()));

        $this->getEventMapper()->persist($event);
    }

    /**
     * Get the event mapper.
     *
     * @return Database\Mapper\Event
     */
    public function getEventMapper()
    {
        return $this->getServiceManager()->get('database_mapper_event');
    }
}
