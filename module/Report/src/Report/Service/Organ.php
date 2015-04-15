<?php

namespace Report\Service;

use Application\Service\AbstractService;

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
