<?php

namespace Database\Service;

use Application\Service\AbstractService;

class InstallationFunction extends AbstractService
{

    /**
     * Get all functions.
     *
     * @return array of InstallationFunction's
     */
    public function getAllFunctions()
    {
        return $this->getFunctionMapper()->findAll();
    }

    /**
     * Get the installation function mapper.
     *
     * @return \Database\Mapper\InstallationFunction
     */
    public function getFunctionMapper()
    {
        return $this->getServiceManager()->get('database_mapper_installationfunction');
    }
}
