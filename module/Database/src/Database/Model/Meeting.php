<?php

namespace Database\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Meeting model.
 *
 * @ORM\Entity
 */
class Meeting
{
    const TYPE_BV = 'bv'; // bestuursvergadering
    const TYPE_AV = 'av'; // algemene leden vergadering
    const TYPE_VV = 'vv'; // voorzitters vergadering
    const TYPE_VIRT = 'virt'; // virtual meeting

    /**
     * Meeting type.
     *
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * Meeting number.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $number;

    /**
     * Meeting date.
     *
     * @ORM\Column(type="date")
     */
    protected $date;

    /**
     * Decisions.
     *
     * @ORM\OneToMany(targetEntity="Decision", mappedBy="meeting")
     */
    protected $decisions;

    /**
     * Allowed meeting types.
     *
     * @var array
     */
    protected static $types = array(
        self::TYPE_BV,
        self::TYPE_AV,
        self::TYPE_VV,
        self::TYPE_VIRT
    );

    /**
     * Get all allowed meeting types.
     */
    public static function getTypes()
    {
        return self::$types;
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->decisions = new ArrayCollection();
    }

    /**
     * Get the meeting type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the meeting number.
     *
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set the meeting type.
     *
     * @param string $type
     */
    public function setType($type)
    {
        if (!in_array($type, self::$types)) {
            throw new \InvalidArgumentException("Invalid meeting type given.");
        }
        $this->type = $type;
    }

    /**
     * Set the meeting number.
     *
     * @param int $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * Get the meeting date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set the meeting date.
     *
     * @param \DateTime $date
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;
    }

    /**
     * Get the decisions.
     *
     * @return array
     */
    public function getDecisions()
    {
        return $this->decisions;
    }

    /**
     * Add a decision.
     *
     * @param Decision $decision
     */
    public function addDecision(Decision $decision)
    {
        $this->decisions[] = $decision;
    }

    /**
     * Add multiple decisions.
     *
     * @param array $decisions
     */
    public function addDecisions($decisions)
    {
        foreach ($decisions as $decision) {
            $this->addDecision($decision);
        }
    }
}
