<?php
namespace Checker\Model\Error;

use Checker\Model\Error;

class MemberHasRoleButNotInOrgan extends Error
{
    /**
     * @var Role that the member has in the organ
     */
    private $role;
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param \Database\Model\Meeting $meeting
     * @param \Database\Model\SubDecision\Installation $installation
     * @param $role The role that the member still has
     */
    public function __construct(
        \Database\Model\Meeting $meeting,
        \Database\Model\SubDecision\Installation $installation,
        $role
    ) {
        parent::__construct($meeting, $installation);
        $this->role = $role;
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
        return 'Member ' . $this->getMember()->getFullName() .
        ' ('. $this->getMember()->getLidNr() . ')'
        . ' has a special role as ' . $this->getRole() . ' in  '
        . $this->getFoundation()->getName() . '  but is not a member anymore';
    }
}
