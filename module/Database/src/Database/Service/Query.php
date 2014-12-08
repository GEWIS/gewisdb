<?php

namespace Database\Service;

use Application\Service\AbstractService;

use Doctrine\ORM\AbstractQuery;

class Query extends AbstractService
{

    /**
     * Execute a query.
     * @param array $data
     * @return mixed result
     */
    public function execute($data)
    {
        $form = $this->getQueryForm();

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $data = $form->getData();

        /**
         * Yes, I know, this is ugly. I should actually make a mapper for this
         * etc. etc. etc. But yes, I'm lazy. So I'm typing a bunch of text
         * instead, to make up it. And yes, probably it would have been better
         * to have made the mapper anyway. Still, I'm lazy.
         *
         * TODO: properly put this in a mapper.....
         */
        $em = $this->getServiceManager()->get('database_doctrine_em');
        $query = $em->createQuery($data['query']);
        return $query->getResult(AbstractQuery::HYDRATE_SCALAR);
    }

    /**
     * Get the query form.
     * @return \Database\Form\Query
     */
    public function getQueryForm()
    {
        return $this->getServiceManager()->get('database_form_query');
    }
}
