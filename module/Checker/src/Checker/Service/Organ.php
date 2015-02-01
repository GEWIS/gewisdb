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
        $createdOrgans = $this->transform($mapper->getAllOrgansCreated($meeting));
        $deletedOrgans = $this->transform($mapper->getAllOrgansDeleted($meeting));
        return array_diff($createdOrgans, $deletedOrgans);
    }

    public function getOrgansCreatedAtMeeting(\Database\Model\Meeting $meeting) {
        $mapper = $this->getServiceManager()->get('checker_mapper_organ');
        return $mapper->getOrgansCreatedAtMeeting($meeting);
    }

    /**
     * Extract the names of all organs
     * @param array $a Array to extract the names from
     * @return array string
     */
    private function transform(array &$a) {
        foreach ($a as $key => &$value) {
            $value = $value['name'];
        }
        return $a;
    }
}