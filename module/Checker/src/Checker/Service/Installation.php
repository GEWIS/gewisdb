<?php
namespace Checker\Service;

use Application\Service\AbstractService;

class Installation extends AbstractService
{
    /**
     * Fetch all the existing organs after the meeting.
     */
    public function getAllMembers($meeting) {
        $mapper = $this->getServiceManager()->get('checker_mapper_installation');
        $createdMembers = $mapper->getAllInstallationsInstalled($meeting);
        $deletedMembers = $mapper->getAllInstallationsDischarged($meeting);

        $members = array();
        foreach ($createdMembers as $cm) {
            $members[$this->getHash($cm)] = $cm;
        }

        foreach ($deletedMembers as $dm)
        {
            $creation = $dm->getInstallation();
            $hash = $this->getHash($creation);
            if (isset($members[$hash])) {
                unset($members[$hash]);
            }
        }

        return $members;
    }

    private function getHash(\Database\Model\SubDecision $d) {
        return $d->getMeetingType() . $d->getMeetingNumber() . $d-> getDecisionPoint() . $d->getNumber();
    }
}