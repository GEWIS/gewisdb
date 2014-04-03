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
class Installation extends FoundationReference
{
    const FUNC_MEMBER = 'member';
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
     * @ORM\Column(type="string")
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
     * Get available functions.
     *
     * @return array
     */
    public static function getFunctions()
    {
        return array(
            self::FUNC_MEMBER,
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
            throw new \InvalidArgumentException("Nonexisting function '$function' given.");
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
     * Get the content.
     *
     * Fixes Bor's greatest frustration
     *
     * @return string
     */
    public function getContent()
    {
        $member = $this->getMember()->getFullName();
        $text = $member . ' wordt gëinstalleerd als ' . $this->getDutchFunction();
        $text .= ' van ' . $this->getFoundation()->getAbbr() . '.';
        return $text;
    }

    /**
     * Function to dutch name.
     *
     * @return string
     */
    protected function getDutchFunction()
    {
        switch ($this->getFunction()) {
        case self::FUNC_MEMBER:
            return 'lid';
            break;
        case self::FUNC_CHAIRMAN:
            return 'voorzitter';
            break;
        case self::FUNC_SECRETARY:
            return 'secretaris';
            break;
        case self::FUNC_TREASURER:
            return 'penningmeester';
            break;
        case self::FUNC_VICE_CHAIRMAN:
            return 'vice-voorzitter';
            break;
        case self::FUNC_PR_OFFICER:
            return 'pr-functionaris';
            break;
        case self::FUNC_EDUCATION_OFFICER:
            return 'onderwijscommissaris';
            break;
        }
    }
}
