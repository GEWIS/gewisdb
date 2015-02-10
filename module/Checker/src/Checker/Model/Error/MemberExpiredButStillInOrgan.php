<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 10-2-15
 * Time: 9:15
 */

namespace Checker\Model\Error;

use Checker\Model\Error;

class MemberExpiredButStillInOrgan extends Error
{
    /**
     * @param \Database\Model\Meeting $meeting
     * @param \Database\Model\SubDecision\Installation $installation
     */
    public function __construct(
        \Database\Model\Meeting $meeting,
        \Database\Model\SubDecision\Installation $installation
    ) {
        parent::__construct($meeting, $installation);
    }

    /**
     * @return \Database\Model\Member Member that is not member of GEWIS anymore
     */
    public function getMember()
    {
        return $this->getSubDecision()->getMember();
    }

    /**
     * @return \Database\Model\Organ Organ that the member is still a member of
     */
    public function getOrgan()
    {
        return $this->getSubDecision()->getFoundation();
    }

    public function asText() {
        return 'Member ' . $this->getMember()->getFullName() . ' is member of ' . $this->getOrgan()->getName()
        . ' however ' . $this->getMember()->getFullName() . ' is not a member of GEWIS anymore';
    }
} 