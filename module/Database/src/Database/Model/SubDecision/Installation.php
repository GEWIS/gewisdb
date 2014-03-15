<?php

namespace Database\Model\SubDecision;

use Doctrine\ORM\Mapping as ORM;

use Database\Model\SubDecision;

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
}
