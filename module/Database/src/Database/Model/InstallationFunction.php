<?php

namespace Database\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Function model.
 *
 * @ORM\Entity
 */
class InstallationFunction
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
}
