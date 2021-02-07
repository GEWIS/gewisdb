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
            case Foundation::ORGAN_TYPE_AVC:
            case Foundation::ORGAN_TYPE_KKK:
            case Foundation::ORGAN_TYPE_RVA:
            case Foundation::ORGAN_TYPE_AVW:
                $type = 1;
                break;
            case Foundation::ORGAN_TYPE_FRATERNITY:
                $type = 5;
                break;
            }
            $year = $organ->getDecision()->getMeeting()->getDate()->format('Y');

            $data = array(
                'orgaantypeid' => $type,
                'jaartal' => $year,
                'orgaanafk' => $organ->getAbbr(),
                'orgaannaam' => $organ->getName()
            );

            $id = $this->getQuery()->checkOrganExists($type, $organ->getAbbr(),
                $organ->getName(), $year);
            if (null === $id) {
                $this->getQuery()->createOrgan($data);
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
