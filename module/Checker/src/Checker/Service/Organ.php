<?php
namespace Checker\Service;

use Application\Service\AbstractService;

class Organ extends AbstractService
{
    /**
     * Fetch all the existing organs after the meeting.
     */
    public function getAllOrgans($meeting) {
        $mapper = $this->getServiceManager()->get('checker_mapper_organ');
        $createdOrgans = $mapper->getAllOrgansCreated($meeting);
        $deletedOrgans = $mapper->getAllOrgansDeleted($meeting);
        return array_diff($createdOrgans, $deletedOrgans);
    }
}