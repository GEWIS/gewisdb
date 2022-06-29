<?php
namespace Checker\Model\Error;

use Checker\Model\Error;
use Database\Model\Meeting as MeetingModel;
use Database\Model\Member as MemberModel;
use Database\Model\SubDecision\Foundation as FoundationModel;
use Database\Model\SubDecision\Installation as InstallationModel;

class MemberHasRoleButNotInOrgan extends Error
{
    /**
     * @var string Role that the member has in the organ
     */
    private $role;

    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param MeetingModel $meeting
     * @param InstallationModel $installation
     * @param $role string The role that the member still has
     */
    public function __construct(
        MeetingModel $meeting,
        InstallationModel $installation,
        $role
    ) {
        parent::__construct($meeting, $installation);
        $this->role = $role;
    }

    /**
     * Return the member that is in a non existing organ
     *
     * @return MemberModel
     */
    public function getMember()
    {
        return $this->getSubDecision()->getMember();
    }

    /**
     * Get the organ that does not exist anymore
     *
     * @return FoundationModel
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
