<?php

namespace Report\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Organ member entity.
 *
 * Note that this entity is derived from the decisions themself.
 *
 * @ORM\Entity
 */
class OrganMember
{

    /**
     * Organ id.
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Organ",inversedBy="members")
     */
    protected $organ;

    /**
     * Member lidnr.
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Member",inversedBy="organInstallations")
     * @ORM\JoinColumn(name="lidnr",referencedColumnName="lidnr")
     */
    protected $member;

    /**
     * Function.
     *
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    protected $function;

    /**
     * Installation date.
     *
     * @ORM\Id
     * @ORM\Column(type="date")
     */
    protected $installDate;

    /**
     * Installation.
     *
     * @ORM\OneToOne(targetEntity="Report\Model\SubDecision\Installation")
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="r_meeting_type", referencedColumnName="meeting_type"),
     *  @ORM\JoinColumn(name="r_meeting_number", referencedColumnName="meeting_number"),
     *  @ORM\JoinColumn(name="r_decision_point", referencedColumnName="decision_point"),
     *  @ORM\JoinColumn(name="r_decision_number", referencedColumnName="decision_number"),
     *  @ORM\JoinColumn(name="r_number", referencedColumnName="number")
     * })
     */
    protected $installation;

    /**
     * Discharge date.
     *
     * @ORM\Column(type="date")
     */
    protected $dischargeDate;
}
