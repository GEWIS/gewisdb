<?php

namespace Database\Service;

use Application\Service\AbstractService;

class Query extends AbstractService
{

    /**
     * Get the query form.
     * @return \Database\Form\Query
     */
    public function getQueryForm()
    {
        return $this->getServiceManager()->get('database_form_query');
    }
}
