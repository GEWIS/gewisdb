<?php

namespace Api\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * API Key model
 *
 * @ORM\Entity
 */
class ApiKey
{

    /**
     * Id
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Name
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * Key
     * @ORM\Column(type="string")
     */
    protected $key;


    /**
     * Get the ID.
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the name.
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name.
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the key.
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the key.
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }
}
