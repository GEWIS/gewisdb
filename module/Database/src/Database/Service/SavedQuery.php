<?php

namespace Database\Service;

use Application\Service\AbstractService;

use Database\Model\SavedQuery as QueryModel;

class SavedQuery extends AbstractService
{

    /**
     * Get all saved query.
     *
     * @return array of SavedQuery's
     */
    public function getAllQueries()
    {
        return $this->getQueryMapper()->findAll();
    }

    /**
     * Get the saved query mapper.
     *
     * @return \Database\Mapper\SavedQuery
     */
    public function getQueryMapper()
    {
        return $this->getServiceManager()->get('database_mapper_savedquery');
    }
}
