<?php

namespace Database\Model\SubDecision;

use Doctrine\ORM\Mapping as ORM;

use Database\Model\SubDecision;
use Database\Model\Member;

/**
 * Installation into organ.
 *
 * @ORM\Entity
 */
class Installation extends SubDecision
{
    const FUNC_CHAIRMAN = 'chairman';
    const FUNC_SECRETARY = 'secretary';
    const FUNC_TREASURER = 'treasurer';
    const FUNC_VICE_CHAIRMAN = 'vice-chairman';
    const FUNC_PR_OFFICER = 'pr-officer';
    const FUNC_EDUCATION_OFFICER = 'education-officer';

    /**
     * Function given.
     *
     * Can only be one of:
     * - chairman
     * - secretary
     * - treasurer
     * - vice-chairman
     * - pr-officer
     * - education-officer
     *
     * @todo Determine values of this for historical reasons
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $function;

    /**
     * Member.
     *
     * @ORM\ManyToOne(targetEntity="Database\Model\Member")
     * @ORM\JoinColumn(name="lidnr", referencedColumnName="lidnr")
     */
    protected $member;

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
     * Get available functions.
     *
     * @return array
     */
    public static function getFunctions()
    {
        return array(
            self::FUNC_CHAIRMAN,
            self::FUNC_SECRETARY,
            self::FUNC_TREASURER,
            self::FUNC_VICE_CHAIRMAN,
            self::FUNC_PR_OFFICER,
            self::FUNC_EDUCATION_OFFICER
        );
    }

    /**
     * Get the function.
     *
     * @return string
     */
    public function getFunction()
    {
        return $this->function;
    }

    /**
     * Set the function.
     *
     * @param string $function
     *
     * @throws \IllegalArgumentException when a nonexisting function is given.
     */
    public function setFunction($function)
    {
        if (!in_array($function, self::getFunctions())) {
            throw \IllegalArgumentException("Nonexisting function given.");
        }
        $this->function = $function;
    }

    /**
     * Get the member.
     *
     * @return Member
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * Set the member.
     *
     * @param Member $member
     */
    public function setMember(Member $member)
    {
        $this->member = $member;
    }

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
