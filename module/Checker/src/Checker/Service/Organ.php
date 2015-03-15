<?php
namespace Checker\Service;

use Application\Service\AbstractService;

class Organ extends AbstractService
{
    /**
     * Get the names of all the organs after $meeting
     *
     * @param \Database\Model\Meeting $meeting
     * @return array string
     */
    public function getAllOrgans(\Database\Model\Meeting $meeting) {
        $mapper = $this->getServiceManager()->get('checker_mapper_organ');
        $createdOrgans = $mapper->getAllOrgansCreated($meeting);
        $deletedOrgans = $mapper->getAllOrgansDeleted($meeting);
        return array_diff($createdOrgans, $deletedOrgans);
    }

    public function getOrgansCreatedAtMeeting(\Database\Model\Meeting $meeting) {
        $mapper = $this->getServiceManager()->get('checker_mapper_organ');
        return $mapper->getOrgansCreatedAtMeeting($meeting);
    }
}
