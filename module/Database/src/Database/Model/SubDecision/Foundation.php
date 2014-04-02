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
     * @return string
     */
    public function getContent()
    {
        switch ($this->getOrganType()) {
        case self::ORGAN_TYPE_COMMITTEE:
            $text = 'Commissie ';
            break;
        case self::ORGAN_TYPE_AV_COMMITTEE:
            $text = 'AV-commissie ';
            break;
        case self::ORGAN_TYPE_FRATERNITY:
            $text = 'Dispuut ';
            break;
        }
        $text .= $this->getName();
        $text .= ' met afkorting ';
        $text .= $this->getAbbr();
        $text .= ' wordt opgericht.';
        return $text;
    }

    /**
     * Get an array with all information.
     *
     * Mostly usefull for usage with JSON.
     *
     * @return array
     */
    public function toArray()
    {
        $decision = $this->getDecision();
        return array(
            'meeting_type' => $decision->getMeeting()->getType(),
            'meeting_number' => $decision->getMeeting()->getNumber(),
            'decision_point' => $decision->getPoint(),
            'decision_number' => $decision->getNumber(),
            'subdecision_number' => $this->getNumber(),
            'abbr' => $this->getAbbr(),
            'name' => $this->getName(),
            'organtype' => $this->getOrganType()
        );
    }
}
