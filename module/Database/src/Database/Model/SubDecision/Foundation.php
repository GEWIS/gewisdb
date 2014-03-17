<?php

namespace Database\Model\SubDecision;

use Doctrine\ORM\Mapping as ORM;

use Database\Model\SubDecision;

/**
 * Foundation of an organ.
 *
 * @ORM\Entity
 */
class Foundation extends SubDecision
{

    const ORGAN_TYPE_COMMITTEE = 'committee';
    const ORGAN_TYPE_AV_COMMITTEE = 'avc';
    const ORGAN_TYPE_FRATERNITY = 'fraternity';

    /**
     * Abbreviation (only for when organs are created)
     *
     * @ORM\Column(type="string")
     */
    protected $abbr;

    /**
     * Name (only for when organs are created)
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * Type of the organ.
     *
     * @ORM\Column(type="string")
     */
    protected $organType;


    /**
     * Get available organ types.
     *
     * @return array
     */
    public function getOrganTypes()
    {
        return array(
            self::ORGAN_TYPE_COMMITTEE,
            self::ORGAN_TYPE_AV_COMMITTEE,
            self::ORGAN_TYPE_FRATERNITY
        );
    }

    /**
     * Get the abbreviation.
     *
     * @return string
     */
    public function getAbbr()
    {
        return $this->abbr;
    }

    /**
     * Set the abbreviation.
     *
     * @param string $abbr
     */
    public function setAbbr($abbr)
    {
        $this->abbr = $abbr;
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
     * Get the type.
     *
     * @return string
     */
    public function getOrganType()
    {
        return $this->organType;
    }

    /**
     * Set the type.
     *
     * @param string $organType
     *
     * @throws \InvalidArgumentException if the type is wrong
     */
    public function setOrganType($organType)
    {
        if (!in_array($organType, self::getOrganTypes())) {
            throw new \InvalidArgumentException("Given type does not exist.");
        }
        $this->organType = $organType;
    }

    /**
     * Get the content.
     *
     * @todo implement this
     *
     * @return string
     */
    public function getContent()
    {
        return 'TODO';
    }
}
