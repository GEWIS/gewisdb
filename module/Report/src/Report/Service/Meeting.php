<?php

namespace Report\Service;

use Application\Service\AbstractService;

class Meeting extends AbstractService
{

    /**
     * Export meetings.
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
