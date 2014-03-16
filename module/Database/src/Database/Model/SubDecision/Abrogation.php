<?php

namespace Database\Model\SubDecision;

use Doctrine\ORM\Mapping as ORM;

use Database\Model\SubDecision;

/**
 * Abrogation of an organ.
 *
 * @ORM\Entity
 */
class Abrogation extends SubDecision
{

    /**
     * Reference to foundation of organ.
     *
     * @ORM\ManyToOne(targetEntity="Foundation")
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
     * Get organ foundation.
     *
     * @return Foundation
     */
    public function getFoundation()
    {
        return $this->foundation;
    }

    /**
     * Set organ foundation.
     *
     * @param Foundation $foundation
     */
    public function setFoundation($foundation)
    {
        $this->foundation = $foundation;
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
