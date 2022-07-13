<?php

namespace Database\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Event model.
 *
 * @ORM\Entity
 */
class Event
{
    /**
     * The event ID.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Name
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * Context of the event.
     *
     * @ORM\Column(type="string")
     */
    protected $context;

    /**
     * Parameters.
     *
     * @ORM\Column(type="text")
     */
    protected $parameters;


    /**
     * Get the ID.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the ID.
     *
     * @param inst $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get the name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the event context.
     *
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set the event context.
     *
     * @param string $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * Get the event parameters.
     *
     * @return string
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Set the event parameters.
     *
     * @param string $parameters
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }
}
