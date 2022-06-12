<?php

namespace Checker\Model\Error;

use Checker\Model\Error;

/**
 * Class MemberInNonExistingOrgan
 *
 * Denotes an error where a member is installed in an organ that either
 * is not yet created, or is abrogated.
 *
 * @package Checker\Model\Error
 */
class MemberInNonExistingOrgan extends Error
{
    public function __construct(
        \Database\Model\Meeting $meeting,
        \Database\Model\SubDecision\Installation $installation
    ) {
        parent::__construct($meeting, $installation);
    }


    /**
     * Return the member that is in a non existing organ
     *
     * @return \Database\Model\Member
     */
    public function getMember()
    {
        return $this->getSubDecision()->getMember();
    }

    /**
     * Get the organ that does not exist anymore
     *
     * @return \Database\Model\SubDecision\Installation
     */
    public function getFoundation()
    {
        return $this->getSubDecision()->getFoundation();
    }


    public function asText()
    {
        return 'Member ' . $this->getMember()->getFullName()
            .    ' (' . $this->getMember()->getLidNr() . ')'
            . ' is still installed as ' . $this->getSubDecision()->getFunction() . ' in '
            . $this->getFoundation()->getName() . ' which does not exist anymore';
    }
}
