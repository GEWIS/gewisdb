<?php

namespace Export\Service;

use Application\Service\AbstractService;

use Database\Model\SubDecision\Foundation;

class Organ extends AbstractService
{

    /**
     * Export organs.
     */
    public function export()
    {
        foreach ($this->getOrganMapper()->findAll() as $organ) {
            // first determine all parameters
            switch ($organ->getOrganType()) {
            case Foundation::ORGAN_TYPE_COMMITTEE:
                $type = 2;
                break;
            case Foundation::ORGAN_TYPE_AV_COMMITTEE:
                $type = 1;
                break;
            case Foundation::ORGAN_TYPE_FRATERNITY:
                $type = 5;
                break;
            }
            $exists = $this->getQuery()->checkOrganExists($type, $organ->getAbbr(), $organ->getName(),
                $organ->getDecision()->getMeeting()->getDate()->format('Y'));
            if ($exists) {
                echo "Exists\n";
            } else {
                echo "Doesn't exist yet\n";
            }
        }
    }

    /**
     * Get the organ mapper.
     *
     * @return \Database\Mapper\Organ
     */
    public function getOrganMapper()
    {
        return $this->getServiceManager()->get('database_mapper_organ');
    }

    /**
     * Get the query object.
     */
    public function getQuery()
    {
        return $this->getServiceManager()->get('export_query_organ');
    }
}
