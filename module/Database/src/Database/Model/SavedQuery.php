<?php

namespace Database\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Saved query model.
 *
 * @ORM\Entity
 */
class SavedQuery
{
    /**
     * The query ID.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Name.
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * The Saved Query.
     *
     * @ORM\Column(type="text")
     */
    protected $query;


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
     * Set the query.
     *
     * @param string $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * Get the query.
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }
}
