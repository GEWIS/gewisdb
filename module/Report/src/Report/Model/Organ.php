<?php

namespace Report\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Organ entity.
 *
 * Note that this entity is derived from the decisions themself.
 *
 * @ORM\Entity
 */
class Organ
{

    const ORGAN_TYPE_COMMITTEE = 'committee';
    const ORGAN_TYPE_AV_COMMITTEE = 'avc';
    const ORGAN_TYPE_FRATERNITY = 'fraternity';

    /**
     * Id.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

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
    protected $type;

    /**
     * Reference to foundation of organ.
     *
     * @ORM\OneToOne(targetEntity="Report\Model\SubDecision\Foundation")
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="r_meeting_type", referencedColumnName="meeting_type"),
     *  @ORM\JoinColumn(name="r_meeting_number", referencedColumnName="meeting_number"),
     *  @ORM\JoinColumn(name="r_decision_point", referencedColumnName="decision_point"),
     *  @ORM\JoinColumn(name="r_decision_number", referencedColumnName="decision_number"),
     *  @ORM\JoinColumn(name="r_number", referencedColumnName="number")
     * })
     */
    protected $foundation;

    /**
     * Foundation date.
     *
     * @ORM\Column(type="date")
     */
    protected $foundationDate;

    /**
     * Abrogation date.
     *
     * @ORM\Column(type="date")
     */
    protected $abrogationDate;

    /**
     * Reference to members.
     *
     * @ORM\OneToMany(targetEntity="OrganMember",mappedBy="organ")
     */
    protected $members;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->members = new ArrayCollection();
    }
}
