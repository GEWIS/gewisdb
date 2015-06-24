<?php
namespace Checker\Service;

use Application\Service\AbstractService;
use \Zend\Stdlib\ArrayUtils;

class Installation extends AbstractService
{
    /**
     * Fetch all the existing organs after $meeting
     *
     * @param \Database\Model\Meeting $meeting
     * @return array \Database\Model\SubDecision\Installation
     */
    public function getAllInstallations(\Database\Model\Meeting $meeting)
    {
        $mapper = $this->getServiceManager()->get('checker_mapper_installation');

        $createdMembers = $mapper->getAllInstallationsInstalled($meeting);
        $deletedMembers = $mapper->getAllInstallationsDischarged($meeting);

        $members = array();
        foreach ($createdMembers as $cm) {
            $members[$this->getHash($cm)] = $cm;
        }

        foreach ($deletedMembers as $dm) {
            $creation = $dm->getInstallation();
            $hash = $this->getHash($creation);
            if (isset($members[$hash])) {
                unset($members[$hash]);
            }
        }

        return $members;
    }

    /**
     * Returns the different roles for each user in each organ
     * @param $meeting
     * @return array \Database\Model\SubDecision\Installation in the form:
     * [
     *      'organName' => [
     *          'memberId' => [
     *              'function' => Installation
     *          ]
     *      ]
     * ]
     */
    public function getCurrentRolesPerOrgan(\Database\Model\Meeting $meeting)
    {
        $installations = $this->getAllInstallations($meeting);

        $roles = [];

        foreach ($installations as $installation) {
            $memberId = $installation->getMember()->getLidNr();
            $function = $installation->getFunction();
            $organName = $installation->getFoundation()->getAbbr();

            $roles[$organName][$memberId][$function] = $installation;

        }
        return $roles;
    }

    /**
     * Returns a unique hash for a subdecision (Needed for matching subdecisions)
     *
     * @param \Database\Model\SubDecision $d Decision to hash for
     * @return string Unique hash for $d
     */
    private function getHash(\Database\Model\SubDecision $d)
    {
        return $d->getMeetingType() . $d->getMeetingNumber() . $d-> getDecisionPoint() . $d->getNumber();
    }
}
