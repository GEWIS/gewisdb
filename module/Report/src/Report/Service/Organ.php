<?php

namespace Report\Service;

use Application\Service\AbstractService;

use Report\Model\Meeting as ReportMeeting;

class Organ extends AbstractService
{

    /**
     * Export organ.
     */
    public function generate()
    {
    }

    /**
     * Get the console object.
     */
    public function getConsole()
    {
        return $this->getServiceManager()->get('console');
    }
}
